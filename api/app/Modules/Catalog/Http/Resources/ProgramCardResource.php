<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ringkasan program untuk kartu katalog / beranda (ProgramCard).
 *
 * @mixin Program
 */
class ProgramCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'category' => $this->whenLoaded('category', fn () => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'summary' => $this->summary,
            'level' => $this->level,
            'duration_label' => $this->duration_label,
            'format_label' => $this->format_label,
            'price' => $this->price,
            'promo_price' => $this->promo_price,
            'selling_price' => $this->sellingPrice(),
            'has_job_guarantee' => $this->has_job_guarantee,
            'thumbnail_url' => $this->thumbnail_url,
            'rating' => $this->rating,
            'rating_count' => $this->rating_count,
            'lessons_count' => $this->whenCounted('lessons'),
            'next_cohort' => $this->whenLoaded('cohorts', fn () => ($cohort = $this->cohorts->first())
                ? new CohortResource($cohort)
                : null),
        ];
    }
}
