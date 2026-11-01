<?php

namespace App\Modules\Catalog\Observers;

use App\Modules\Catalog\Models\Cohort;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Program;
use App\Modules\Catalog\Models\Section;
use App\Modules\Catalog\ProgramCache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

/**
 * Membuang cache detail program setiap kali program atau turunannya berubah.
 * `updated` (bukan `saved`) supaya ikut terpicu oleh increment/decrement kuota cohort.
 */
class ProgramCacheObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Model $model): void
    {
        $this->flush($model);
    }

    public function updated(Model $model): void
    {
        $this->flush($model);
    }

    public function deleted(Model $model): void
    {
        $this->flush($model);
    }

    private function flush(Model $model): void
    {
        $program = match (true) {
            $model instanceof Program => $model,
            $model instanceof Cohort, $model instanceof Section => $model->program,
            $model instanceof Lesson => $model->section?->program,
            default => null,
        };

        if ($program) {
            ProgramCache::forget($program);
        }
    }
}
