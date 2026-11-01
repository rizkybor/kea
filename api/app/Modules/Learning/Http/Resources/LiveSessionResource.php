<?php

namespace App\Modules\Learning\Http\Resources;

use App\Modules\Learning\Models\LiveSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LiveSession
 */
class LiveSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'join_opens_at' => $this->joinOpensAt()->toIso8601String(),
            'is_joinable' => $this->isJoinable(),
            'recording_url' => $this->ends_at->isPast() ? $this->recording_url : null,
            'mentor_name' => $this->whenLoaded('mentor', fn () => $this->mentor?->name),
            'lesson_id' => $this->lesson_id,
        ];
    }
}
