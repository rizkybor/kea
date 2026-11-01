<?php

namespace App\Modules\Learning\Http\Resources;

use App\Modules\Learning\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Butuh eager load: program, cohort (dengan held_sessions_count), certificate,
 * withCount attendances, withAvg submissions score.
 *
 * @mixin Enrollment
 */
class EnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'progress_pct' => $this->progress_pct,
            'resume_lesson_id' => $this->last_lesson_id,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'program' => [
                'slug' => $this->program->slug,
                'title' => $this->program->title,
                'type' => $this->program->type->value,
                'type_label' => $this->program->type->label(),
                'thumbnail_url' => $this->program->thumbnail_url,
            ],
            'cohort' => $this->cohort ? [
                'id' => $this->cohort->id,
                'name' => $this->cohort->name,
                'starts_at' => $this->cohort->starts_at?->toIso8601String(),
                'community_url' => $this->cohort->community_url,
            ] : null,
            'attendance' => $this->cohort ? [
                'attended' => (int) $this->attendances_count,
                'held' => (int) ($this->cohort->held_sessions_count ?? 0),
            ] : null,
            'average_score' => $this->submissions_avg_score !== null ? (int) round((float) $this->submissions_avg_score) : null,
            'certificate_code' => $this->whenLoaded('certificate', fn () => $this->certificate?->code),
        ];
    }
}
