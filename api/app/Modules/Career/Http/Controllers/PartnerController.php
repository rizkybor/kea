<?php

namespace App\Modules\Career\Http\Controllers;

use App\Modules\Career\Enums\ApplicationStatus;
use App\Modules\Career\Enums\EmploymentType;
use App\Modules\Career\Enums\JobStatus;
use App\Modules\Career\Enums\WorkMode;
use App\Modules\Career\Http\Resources\ApplicantResource;
use App\Modules\Career\Http\Resources\JobPostingResource;
use App\Modules\Career\Http\Resources\TalentResource;
use App\Modules\Career\Models\Company;
use App\Modules\Career\Models\JobApplication;
use App\Modules\Career\Models\JobPosting;
use App\Modules\Catalog\Models\Category;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Support\ApiException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Portal hiring partner: kelola lowongan & pipeline pelamar (JOB-01/03) dan cari talent (JOB-02).
 * Semua data di-scope ke perusahaan milik akun partner.
 */
class PartnerController
{
    private const TALENT_RELATIONS = ['enrollments' => ['program', 'cohort', 'certificate']];

    public function company(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        return response()->json(['data' => [
            ...$company->only('id', 'name', 'slug', 'industry', 'city', 'website', 'logo_url', 'is_verified'),
            'open_jobs' => $company->jobPostings()->where('status', JobStatus::Open)->count(),
            'applications' => JobApplication::whereIn('job_posting_id', $company->jobPostings()->select('id'))->count(),
            'hired' => JobApplication::whereIn('job_posting_id', $company->jobPostings()->select('id'))->where('status', ApplicationStatus::Hired)->count(),
        ]]);
    }

    public function jobs(Request $request): AnonymousResourceCollection
    {
        $jobs = $this->resolveCompany($request)->jobPostings()->with(['company', 'category'])->withCount('applications')->latest()->get();

        return tap(JobPostingResource::collection($jobs), fn ($collection) => $collection->collection->each->forOwner());
    }

    public function showJob(Request $request, int $job): JobPostingResource
    {
        return (new JobPostingResource($this->ownedJob($request, $job)->load(['company', 'category'])->loadCount('applications')))->forOwner();
    }

    public function storeJob(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);
        $data = $this->validateJob($request);

        $job = $company->jobPostings()->create([
            ...$data,
            'slug' => Str::slug($data['title'].' '.$company->name).'-'.Str::lower(Str::random(5)),
            'posted_by' => $request->user()->id,
            'published_at' => $data['status'] === JobStatus::Open->value ? now() : null,
        ]);

        return (new JobPostingResource($job->load(['company', 'category'])))->forOwner()->response()->setStatusCode(201);
    }

    public function updateJob(Request $request, int $job): JobPostingResource
    {
        $model = $this->ownedJob($request, $job);
        $data = $this->validateJob($request);

        if ($data['status'] === JobStatus::Open->value && ! $model->published_at) {
            $data['published_at'] = now();
        }
        $model->update($data);

        return (new JobPostingResource($model->load(['company', 'category'])->loadCount('applications')))->forOwner();
    }

    public function applicants(Request $request, int $job): AnonymousResourceCollection
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(ApplicationStatus::class)]])['status'] ?? null;

        $applications = $this->ownedJob($request, $job)->applications()
            ->with(['user' => self::TALENT_RELATIONS, 'events.actor'])
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->where('status', '!=', ApplicationStatus::Withdrawn)
            ->oldest()
            ->get();

        return ApplicantResource::collection($applications);
    }

    public function updateApplication(Request $request, int $application): ApplicantResource
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_map(fn ($s) => $s->value, ApplicationStatus::pipeline()))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $model = $this->ownedApplication($request, $application);

        if ($model->status->isFinal()) {
            throw new ApiException('Status lamaran ini sudah final.', 'application_final', 409);
        }

        $model->transitionTo(ApplicationStatus::from($data['status']), $request->user(), $data['note'] ?? null);

        return new ApplicantResource($model->load(['user' => self::TALENT_RELATIONS, 'events.actor']));
    }

    public function applicationCv(Request $request, int $application): StreamedResponse
    {
        $model = $this->ownedApplication($request, $application);
        abort_unless($model->cv_path && Storage::disk('local')->exists($model->cv_path), 404);

        return Storage::disk('local')->download($model->cv_path, Str::slug($model->user->name).'-cv.pdf');
    }

    /**
     * Talent pool: alumni lulus yang memberi consent, bisa dicari per skill, program, dan nilai.
     */
    public function talents(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'skill' => ['nullable', 'string', 'max:50'],
            'program' => ['nullable', 'string'],
            'min_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'open_to_work' => ['nullable', 'boolean'],
        ]);
        $this->resolveCompany($request);

        $talents = $this->talentQuery()
            ->with(self::TALENT_RELATIONS)
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $w) => $w
                ->where('name', 'ilike', "%{$term}%")->orWhere('headline', 'ilike', "%{$term}%")))
            ->when($filters['skill'] ?? null, fn (Builder $q, string $skill) => $q->whereRaw(
                'exists (select 1 from jsonb_array_elements_text(coalesce(users.skills, \'[]\'::jsonb)) s where lower(s) = lower(?))', [trim($skill)]))
            ->when($filters['program'] ?? null, fn (Builder $q, string $slug) => $q->whereHas('enrollments', fn (Builder $e) => $e
                ->where('status', EnrollmentStatus::Completed)->whereHas('program', fn (Builder $p) => $p->where('slug', $slug))))
            ->when(isset($filters['min_score']), fn (Builder $q) => $q->whereHas('enrollments.certificate', fn (Builder $c) => $c->where('final_score', '>=', $filters['min_score'])))
            ->when($request->boolean('open_to_work'), fn (Builder $q) => $q->where('open_to_work', true))
            ->orderByDesc('open_to_work')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return TalentResource::collection($talents);
    }

    public function talent(Request $request, int $user): TalentResource
    {
        $this->resolveCompany($request);

        return new TalentResource($this->talentQuery()->with(self::TALENT_RELATIONS)->findOrFail($user));
    }

    public function talentCv(Request $request, int $user): StreamedResponse
    {
        $this->resolveCompany($request);
        $talent = $this->talentQuery()->findOrFail($user);
        abort_unless($talent->cv_path && Storage::disk('local')->exists($talent->cv_path), 404);

        return Storage::disk('local')->download($talent->cv_path, Str::slug($talent->name).'-cv.pdf');
    }

    /**
     * @return Builder<User>
     */
    private function talentQuery(): Builder
    {
        return User::role(Role::Learner->value)
            ->whereNotNull('talent_consent_at')
            ->whereHas('enrollments', fn (Builder $e) => $e->where('status', EnrollmentStatus::Completed));
    }

    private function resolveCompany(Request $request): Company
    {
        return $request->user()->partnerCompany ?? throw new ApiException('Akun partner belum terhubung ke perusahaan. Hubungi tim KEA.', 'no_company', 403);
    }

    private function ownedJob(Request $request, int $id): JobPosting
    {
        return $this->resolveCompany($request)->jobPostings()->findOrFail($id);
    }

    private function ownedApplication(Request $request, int $id): JobApplication
    {
        return JobApplication::whereKey($id)
            ->whereIn('job_posting_id', $this->resolveCompany($request)->jobPostings()->select('id'))
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateJob(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category_slug' => ['nullable', 'string', 'exists:categories,slug'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'work_mode' => ['required', Rule::enum(WorkMode::class)],
            'location' => ['nullable', 'string', 'max:120'],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'gte:salary_min'],
            'show_salary' => ['boolean'],
            'description' => ['required', 'string', 'max:10000'],
            'requirements' => ['nullable', 'array', 'max:15'],
            'requirements.*' => ['string', 'max:300'],
            'skills' => ['nullable', 'array', 'max:15'],
            'skills.*' => ['string', 'max:50'],
            'alumni_only' => ['boolean'],
            'status' => ['required', Rule::enum(JobStatus::class)],
            'closes_at' => ['nullable', 'date', 'after:today'],
        ]);

        $data['category_id'] = isset($data['category_slug']) ? Category::where('slug', $data['category_slug'])->value('id') : null;
        unset($data['category_slug']);

        return $data;
    }
}
