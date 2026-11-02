<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\Cohort;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cohort
 */
class CohortResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'schedule_type' => $this->schedule_type,
            'schedule_label' => $this->schedule_label,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'quota' => $this->quota,
            'seats_left' => $this->seatsLeft(),
            'is_available' => $this->isAvailable(),
        ];
    }
}
