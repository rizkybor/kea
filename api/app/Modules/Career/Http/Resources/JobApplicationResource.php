<?php

namespace App\Modules\Career\Http\Resources;

use App\Modules\Career\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lamaran dari sisi learner: lowongan + status + riwayat.
 *
 * @mixin JobApplication
 */
class JobApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'cover_letter' => $this->cover_letter,
            'portfolio_url' => $this->portfolio_url,
            'applied_at' => $this->created_at->toIso8601String(),
            'hired_at' => $this->hired_at?->toIso8601String(),
            'job' => new JobPostingResource($this->whenLoaded('jobPosting')),
            'events' => $this->whenLoaded('events', fn () => $this->events->map(fn ($e) => [
                'status' => $e->status->value,
                'status_label' => $e->status->label(),
                'note' => $e->note,
                'at' => $e->created_at->toIso8601String(),
            ])),
        ];
    }
}
