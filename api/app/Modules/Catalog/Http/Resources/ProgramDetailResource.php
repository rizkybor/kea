<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\Program;
use Illuminate\Http\Request;

/**
 * @mixin Program
 */
class ProgramDetailResource extends ProgramCardResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'installment_from' => $this->installment_from,
            'preview_video_url' => $this->preview_video_url,
            'benefits' => $this->benefits ?? [],
            'faqs' => $this->faqs ?? [],
            'cohorts' => CohortResource::collection($this->whenLoaded('cohorts')),
            'sections' => $this->sections->map(fn ($section) => [
                'title' => $section->title,
                'topics' => $section->topics ?? [],
                'lessons' => $section->lessons->map(fn ($lesson) => [
                    'title' => $lesson->title,
                    'type' => $lesson->type->value,
                    'kind_label' => $lesson->kindLabel(),
                    'is_preview' => $lesson->is_preview,
                ]),
            ]),
            'mentors' => $this->cohorts->flatMap->mentors->unique('id')->values()->map(fn ($mentor) => [
                'name' => $mentor->name,
                'headline' => $mentor->headline,
                'company' => $mentor->company,
                'avatar_url' => $mentor->avatar_url,
            ]),
            'testimonials' => TestimonialResource::collection($this->whenLoaded('testimonials')),
        ];
    }
}
