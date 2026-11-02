<?php

namespace App\Modules\Learning\Actions;

use App\Modules\Catalog\Models\Lesson;
use App\Modules\Learning\Models\Enrollment;

class RecalculateProgress
{
    public function handle(Enrollment $enrollment): int
    {
        $lessonIds = Lesson::whereHas('section', fn ($q) => $q->where('program_id', $enrollment->program_id))->pluck('id');

        $completed = $enrollment->lessonProgress()
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->count();

        $pct = $lessonIds->isEmpty() ? 0 : (int) floor($completed * 100 / $lessonIds->count());

        $enrollment->update(['progress_pct' => $pct]);

        return $pct;
    }
}
