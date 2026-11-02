<?php

namespace App\Modules\Career\Http\Controllers;

use App\Modules\Career\Actions\ApplyToJob;
use App\Modules\Career\Enums\ApplicationStatus;
use App\Modules\Career\Enums\EmploymentType;
use App\Modules\Career\Enums\WorkMode;
use App\Modules\Career\Http\Resources\JobApplicationResource;
use App\Modules\Career\Http\Resources\JobPostingResource;
use App\Modules\Career\Models\JobApplication;
use App\Modules\Career\Models\JobPosting;
use App\Support\ApiException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * Job board alumni (JOB-01).
 */
class JobController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string'],
            'type' => ['nullable', Rule::enum(EmploymentType::class)],
            'mode' => ['nullable', Rule::enum(WorkMode::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $jobs = JobPosting::open()
            ->with(['company', 'category'])
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $w) => $w
                ->where('title', 'ilike', "%{$term}%")
                ->orWhere('location', 'ilike', "%{$term}%")
                ->orWhereHas('company', fn (Builder $c) => $c->where('name', 'ilike', "%{$term}%"))))
            ->when($filters['category'] ?? null, fn (Builder $q, string $slug) => $q->whereHas('category', fn (Builder $c) => $c->where('slug', $slug)))
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('employment_type', $type))
            ->when($filters['mode'] ?? null, fn (Builder $q, string $mode) => $q->where('work_mode', $mode))
            ->latest('published_at')
            ->paginate($filters['per_page'] ?? 12)
            ->withQueryString();

        return JobPostingResource::collection($jobs);
    }

    public function show(string $slug): JobPostingResource
    {
        $job = JobPosting::open()->where('slug', $slug)->with(['company', 'category'])->firstOrFail();

        return new JobPostingResource($job);
    }

    public function apply(Request $request, string $slug, ApplyToJob $apply): JsonResponse
    {
        $data = $request->validate([
            'cover_letter' => ['nullable', 'string', 'max:3000'],
            'portfolio_url' => ['nullable', 'url:https,http', 'max:255'],
        ]);
        $job = JobPosting::where('slug', $slug)->firstOrFail();

        $application = $apply->handle($request->user(), $job, $data['cover_letter'] ?? null, $data['portfolio_url'] ?? null);

        return (new JobApplicationResource($application->load(['jobPosting.company', 'events'])))->response()->setStatusCode(201);
    }

    public function myApplications(Request $request): AnonymousResourceCollection
    {
        $applications = $request->user()->jobApplications()
            ->with(['jobPosting.company', 'events'])
            ->latest()
            ->get();

        return JobApplicationResource::collection($applications);
    }

    public function withdraw(Request $request, JobApplication $application): JobApplicationResource
    {
        abort_unless($application->user_id === $request->user()->id, 404);

        if ($application->status->isFinal()) {
            throw new ApiException('Lamaran ini sudah selesai diproses.', 'application_final', 409);
        }

        $application->transitionTo(ApplicationStatus::Withdrawn, $request->user());

        return new JobApplicationResource($application->load(['jobPosting.company', 'events']));
    }
}
