<?php

namespace App\Modules\Learning\Http\Controllers;

use App\Modules\Catalog\Models\Lesson;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Http\Resources\EnrollmentResource;
use App\Modules\Learning\Http\Resources\LiveSessionResource;
use App\Modules\Learning\Models\Enrollment;
use App\Modules\Learning\Models\LiveSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $enrollments = EnrollmentController::withSummary($user->enrollments()->getQuery())
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->latest()
            ->get();

        $active = $enrollments->where('status', EnrollmentStatus::Active);
        $cohortIds = $active->pluck('cohort_id')->filter();

        $nextLive = LiveSession::with(['mentor', 'cohort.program'])
            ->whereIn('cohort_id', $cohortIds)
            ->where('ends_at', '>', now())
            ->orderBy('starts_at')
            ->first();

        return response()->json(['data' => [
            'next_live_session' => $nextLive ? [
                ...(new LiveSessionResource($nextLive))->resolve(),
                'program_title' => $nextLive->cohort->program->title,
                'enrollment_id' => $active->firstWhere('cohort_id', $nextLive->cohort_id)?->id,
            ] : null,
            'enrollments' => EnrollmentResource::collection($enrollments),
            'tasks' => $this->tasks($active),
            'profile_complete' => $user->cv_path !== null && $user->headline !== null,
        ]]);
    }

    /**
     * Tugas dari semua enrollment aktif, yang belum dinilai didahulukan, lalu berdasarkan deadline.
     *
     * @param  Collection<int, Enrollment>  $enrollments
     * @return list<array<string, mixed>>
     */
    private function tasks($enrollments): array
    {
        $tasks = [];

        foreach ($enrollments as $enrollment) {
            $lessons = Lesson::whereHas('section', fn ($q) => $q->where('program_id', $enrollment->program_id))
                ->whereNotNull('assignment_title')
                ->get();
            $submissions = $enrollment->submissions()->get()->keyBy('lesson_id');

            foreach ($lessons as $lesson) {
                $submission = $submissions->get($lesson->id);
                $dueAt = $lesson->assignmentDueAt($enrollment->cohort);

                $tasks[] = [
                    'enrollment_id' => $enrollment->id,
                    'lesson_id' => $lesson->id,
                    'title' => $lesson->assignment_title,
                    'program_title' => $enrollment->program->title,
                    'due_at' => $dueAt?->toIso8601String(),
                    'status' => $submission?->status->value ?? 'pending',
                    'score' => $submission?->score,
                ];
            }
        }

        $rank = ['pending' => 0, 'revision' => 0, 'submitted' => 1, 'reviewed' => 2];

        usort($tasks, fn ($a, $b) => [$rank[$a['status']], $a['due_at'] ?? '9999'] <=> [$rank[$b['status']], $b['due_at'] ?? '9999']);

        return array_slice($tasks, 0, 6);
    }
}
