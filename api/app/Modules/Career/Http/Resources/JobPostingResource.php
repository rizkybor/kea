<?php

namespace App\Modules\Career\Http\Resources;

use App\Modules\Career\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin JobPosting
 */
class JobPostingResource extends JsonResource
{
    /**
     * Sertakan field yang hanya untuk pemilik lowongan (status, gaji walau disembunyikan, jumlah pelamar).
     */
    public bool $forOwner = false;

    public function forOwner(): static
    {
        $this->forOwner = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $showSalary = $this->show_salary || $this->forOwner;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'company' => $this->whenLoaded('company', fn () => [
                'name' => $this->company->name,
                'slug' => $this->company->slug,
                'industry' => $this->company->industry,
                'city' => $this->company->city,
                'logo_url' => $this->company->logo_url,
                'website' => $this->company->website,
                'description' => $this->company->description,
                'is_verified' => $this->company->is_verified,
            ]),
            'category' => $this->whenLoaded('category', fn () => $this->category?->only('name', 'slug')),
            'employment_type' => $this->employment_type->value,
            'employment_type_label' => $this->employment_type->label(),
            'work_mode' => $this->work_mode->value,
            'work_mode_label' => $this->work_mode->label(),
            'location' => $this->location,
            'salary_min' => $showSalary ? $this->salary_min : null,
            'salary_max' => $showSalary ? $this->salary_max : null,
            'show_salary' => $this->show_salary,
            'skills' => $this->skills ?? [],
            'alumni_only' => $this->alumni_only,
            'published_at' => $this->published_at?->toIso8601String(),
            'closes_at' => $this->closes_at?->toIso8601String(),
            'is_open' => $this->isOpen(),
            'description' => $this->when($request->routeIs('jobs.show') || $this->forOwner, $this->description),
            'requirements' => $this->when($request->routeIs('jobs.show') || $this->forOwner, $this->requirements ?? []),
            'status' => $this->when($this->forOwner, fn () => $this->status->value),
            'applications_count' => $this->whenCounted('applications'),
        ];
    }
}
