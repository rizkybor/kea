<?php

namespace App\Modules\Career\Http\Resources;

use App\Modules\Identity\Models\User;
use App\Modules\Learning\Enums\EnrollmentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Profil talent untuk hiring partner (JOB-02). Email & nomor HP tidak dibuka; kontak lewat lamaran/LinkedIn.
 * Butuh eager load: enrollments.program, enrollments.cohort, enrollments.certificate.
 *
 * @mixin User
 */
class TalentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $completed = $this->enrollments->where('status', EnrollmentStatus::Completed);
        $scores = $completed->map(fn ($e) => $e->certificate?->final_score)->filter();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'headline' => $this->headline,
            'city' => $this->city,
            'bio' => $this->bio,
            'skills' => $this->skills ?? [],
            'open_to_work' => $this->open_to_work,
            'portfolio_url' => $this->portfolio_url,
            'linkedin_url' => $this->linkedin_url,
            'has_cv' => $this->cv_path !== null,
            'best_score' => $scores->max(),
            'programs' => $completed->values()->map(fn ($e) => [
                'title' => $e->program->title,
                'cohort_name' => $e->cohort?->name,
                'completed_at' => $e->completed_at?->toIso8601String(),
                'final_score' => $e->certificate?->final_score,
                'certificate_code' => $e->certificate?->code,
            ]),
        ];
    }
}
