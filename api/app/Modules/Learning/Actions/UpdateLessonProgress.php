<?php

namespace App\Modules\Learning\Actions;

use App\Modules\Catalog\Models\Lesson;
use App\Modules\Learning\Models\Enrollment;
use App\Modules\Learning\Models\LessonProgress;

class UpdateLessonProgress
{
    public function __construct(
        private RecalculateProgress $recalculate,
        private EvaluateCompletion $evaluate,
    ) {}

    public function handle(Enrollment $enrollment, Lesson $lesson, ?int $positionSec, ?bool $completed): LessonProgress
    {
        $progress = LessonProgress::firstOrNew(['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id]);

        if ($positionSec !== null) {
            $progress->last_position_sec = $positionSec;
        }

        $completionChanged = false;
        if ($completed !== null && $completed !== ($progress->completed_at !== null)) {
            $progress->completed_at = $completed ? now() : null;
            $completionChanged = true;
        }

        $progress->save();
        $enrollment->update(['last_lesson_id' => $lesson->id]);

        if ($completionChanged) {
            $this->recalculate->handle($enrollment);
            $this->evaluate->handle($enrollment);
        }

        return $progress;
    }
}
