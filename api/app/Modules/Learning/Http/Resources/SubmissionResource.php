<?php

namespace App\Modules\Learning\Http\Resources;

use App\Modules\Learning\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Submission
 */
class SubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'link_url' => $this->link_url,
            'file_name' => $this->file_name,
            'note' => $this->note,
            'score' => $this->score,
            'feedback' => $this->feedback,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'graded_at' => $this->graded_at?->toIso8601String(),
            'lesson' => $this->whenLoaded('lesson', fn () => [
                'id' => $this->lesson->id,
                'title' => $this->lesson->title,
                'assignment_title' => $this->lesson->assignment_title,
                'assignment_instructions' => $this->lesson->assignment_instructions,
            ]),
            'learner' => $this->whenLoaded('enrollment', fn () => [
                'name' => $this->enrollment->user->name,
                'program_title' => $this->enrollment->program->title,
                'cohort_name' => $this->enrollment->cohort?->name,
            ]),
        ];
    }
}
