<?php

namespace App\Modules\Learning\Http\Controllers;

use App\Modules\Learning\Enums\SubmissionStatus;
use App\Modules\Learning\Http\Resources\EnrollmentResource;
use App\Modules\Learning\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EnrollmentController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $enrollments = self::withSummary($request->user()->enrollments()->getQuery())
            ->where('status', '!=', 'refunded')
            ->latest()
            ->get();

        return EnrollmentResource::collection($enrollments);
    }

    /**
     * Kurikulum lengkap + status tiap lesson untuk sidebar Ruang Kelas.
     */
    public function curriculum(Enrollment $enrollment): JsonResponse
    {
        Gate::authorize('view', $enrollment);

        $enrollment = self::withSummary(Enrollment::whereKey($enrollment->id))->firstOrFail();
        $enrollment->program->load('sections.lessons');

        $completed = $enrollment->lessonProgress()->whereNotNull('completed_at')->pluck('lesson_id')->flip();
        $submissions = $enrollment->submissions()->pluck('status', 'lesson_id');

        $sections = $enrollment->program->sections->map(fn ($section) => [
            'title' => $section->title,
            'lessons' => $section->lessons->map(fn ($lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'type' => $lesson->type->value,
                'kind_label' => $lesson->kindLabel(),
                'completed' => $completed->has($lesson->id),
                'has_assignment' => $lesson->hasAssignment(),
                'submission_status' => $submissions->get($lesson->id)?->value,
            ]),
        ]);

        $firstIncomplete = $sections->flatMap(fn ($s) => $s['lessons'])->first(fn ($l) => ! $l['completed']);

        return response()->json([
            'data' => [
                'enrollment' => new EnrollmentResource($enrollment),
                'sections' => $sections,
                'resume_lesson_id' => $enrollment->last_lesson_id ?? $firstIncomplete['id'] ?? $sections->first()['lessons'][0]['id'] ?? null,
            ],
        ]);
    }

    /**
     * @param  Builder<Enrollment>  $query
     * @return Builder<Enrollment>
     */
    public static function withSummary(Builder $query): Builder
    {
        return $query
            ->with([
                'program',
                'certificate',
                'cohort' => fn (BelongsTo $q) => $q->withCount(['liveSessions as held_sessions_count' => fn (Builder $s) => $s->where('starts_at', '<', now())]),
            ])
            ->withCount('attendances')
            ->withAvg(['submissions' => fn (Builder $s) => $s->where('status', SubmissionStatus::Reviewed)], 'score');
    }
}
