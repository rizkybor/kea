<?php

namespace App\Modules\Career\Http\Resources;

use App\Modules\Career\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pelamar dari sisi hiring partner: profil talent + status pipeline.
 *
 * @mixin JobApplication
 */
class ApplicantResource extends JsonResource
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
            'has_cv' => $this->cv_path !== null,
            'cv_url' => $this->cv_path ? "/api/v1/partner/applications/{$this->id}/cv" : null,
            // Pelamar membagikan kontaknya ke perusahaan saat melamar.
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'talent' => new TalentResource($this->user),
            'events' => $this->events->map(fn ($e) => [
                'status' => $e->status->value,
                'status_label' => $e->status->label(),
                'note' => $e->note,
                'actor' => $e->actor?->name,
                'at' => $e->created_at->toIso8601String(),
            ]),
        ];
    }
}
