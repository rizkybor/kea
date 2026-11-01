<?php

namespace App\Modules\Learning\Http\Controllers;

use App\Modules\Identity\Enums\Role;
use App\Modules\Learning\Actions\GradeSubmission;
use App\Modules\Learning\Enums\SubmissionStatus;
use App\Modules\Learning\Http\Resources\SubmissionResource;
use App\Modules\Learning\Models\Submission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MentorSubmissionController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(SubmissionStatus::class)]])['status'] ?? SubmissionStatus::Submitted->value;

        $submissions = $this->scoped($request)
            ->with(['lesson', 'enrollment.user', 'enrollment.program', 'enrollment.cohort'])
            ->where('status', $status)
            ->oldest('submitted_at')
            ->paginate(20);

        return SubmissionResource::collection($submissions);
    }

    public function grade(Request $request, int $submission, GradeSubmission $grade): SubmissionResource
    {
        $data = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in([SubmissionStatus::Reviewed->value, SubmissionStatus::Revision->value])],
        ]);

        $model = $this->scoped($request)->findOrFail($submission);

        return new SubmissionResource(
            $grade->handle($model, $request->user(), $data['score'], $data['feedback'] ?? null, SubmissionStatus::from($data['status']))
                ->load(['lesson', 'enrollment.user', 'enrollment.program', 'enrollment.cohort'])
        );
    }

    /**
     * Mentor hanya melihat submission dari cohort yang dipegangnya; PM/admin melihat semua.
     *
     * @return Builder<Submission>
     */
    private function scoped(Request $request): Builder
    {
        $user = $request->user();
        $query = Submission::query();

        if (! $user->hasAnyRole([Role::ProgramManager->value, Role::Admin->value])) {
            $query->whereHas('enrollment', fn (Builder $q) => $q->whereIn('cohort_id', $user->mentoredCohorts()->select('cohorts.id')));
        }

        return $query;
    }
}
