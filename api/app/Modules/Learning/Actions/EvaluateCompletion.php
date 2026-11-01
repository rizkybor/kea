<?php

namespace App\Modules\Learning\Actions;

use App\Modules\Catalog\Models\Lesson;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Enums\SubmissionStatus;
use App\Modules\Learning\Events\EnrollmentCompleted;
use App\Modules\Learning\Models\Enrollment;
use App\Modules\Learning\Models\LiveSession;

/**
 * Syarat lulus default (PRD §8): progres 100%, kehadiran live ≥ 80%,
 * setiap tugas wajib (termasuk final project) sudah direview dengan nilai ≥ 70.
 */
class EvaluateCompletion
{
    public const MIN_ATTENDANCE_RATIO = 0.8;

    public const MIN_ASSIGNMENT_SCORE = 70;

    public function handle(Enrollment $enrollment): bool
    {
        if ($enrollment->status !== EnrollmentStatus::Active || ! $this->meetsRequirements($enrollment)) {
            return false;
        }

        $enrollment->update(['status' => EnrollmentStatus::Completed, 'completed_at' => now()]);

        EnrollmentCompleted::dispatch($enrollment->id);

        return true;
    }

    public function meetsRequirements(Enrollment $enrollment): bool
    {
        if ($enrollment->progress_pct < 100) {
            return false;
        }

        if ($enrollment->cohort_id) {
            $held = LiveSession::where('cohort_id', $enrollment->cohort_id)->where('starts_at', '<', now())->count();
            $attended = $enrollment->attendances()->count();

            if ($held > 0 && $attended / $held < self::MIN_ATTENDANCE_RATIO) {
                return false;
            }
        }

        $assignmentIds = Lesson::whereHas('section', fn ($q) => $q->where('program_id', $enrollment->program_id))
            ->whereNotNull('assignment_title')
            ->pluck('id');

        $passed = $enrollment->submissions()
            ->whereIn('lesson_id', $assignmentIds)
            ->where('status', SubmissionStatus::Reviewed)
            ->where('score', '>=', self::MIN_ASSIGNMENT_SCORE)
            ->count();

        return $passed === $assignmentIds->count();
    }
}
