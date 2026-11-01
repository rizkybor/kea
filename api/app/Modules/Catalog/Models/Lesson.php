<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\LessonType;
use App\Modules\Catalog\Observers\ProgramCacheObserver;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'section_id', 'title', 'type', 'position', 'duration_minutes', 'video_id', 'summary', 'resource_url', 'is_preview',
    'assignment_title', 'assignment_instructions', 'assignment_due_day', 'is_final_project',
])]
#[ObservedBy(ProgramCacheObserver::class)]
class Lesson extends Model
{
    use SoftDeletes;

    public const LOCAL_TIMEZONE = 'Asia/Jakarta';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'is_preview' => 'boolean',
            'is_final_project' => 'boolean',
        ];
    }

    public function hasAssignment(): bool
    {
        return $this->assignment_title !== null;
    }

    /**
     * Deadline tugas = hari ke-N sejak cohort mulai, pukul 23.59 WIB. Null untuk video course / tanpa tugas.
     */
    public function assignmentDueAt(?Cohort $cohort): ?CarbonInterface
    {
        if ($this->assignment_due_day === null || ! $cohort) {
            return null;
        }

        return $cohort->starts_at->copy()
            ->setTimezone(self::LOCAL_TIMEZONE)
            ->addDays($this->assignment_due_day)
            ->setTime(23, 59);
    }

    /**
     * Label singkat untuk daftar kurikulum, mis. "Video · 18 menit".
     */
    public function kindLabel(): string
    {
        $parts = [$this->type->label()];
        if ($this->duration_minutes) {
            $parts[] = $this->duration_minutes.' menit';
        }
        if ($this->hasAssignment() && $this->type !== LessonType::Assignment) {
            $parts[] = $this->assignment_title;
        }

        return implode(' · ', $parts);
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
