<?php

namespace App\Modules\Identity\Http\Resources;

use App\Modules\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->hasVerifiedEmail(),
            'phone' => $this->phone,
            'headline' => $this->headline,
            'company' => $this->company,
            'bio' => $this->bio,
            'avatar_url' => $this->avatar_url,
            'portfolio_url' => $this->portfolio_url,
            'linkedin_url' => $this->linkedin_url,
            'has_cv' => $this->cv_path !== null,
            'city' => $this->city,
            'skills' => $this->skills ?? [],
            'open_to_work' => $this->open_to_work,
            'talent_consent' => $this->talent_consent_at !== null,
            'partner_company' => $this->partnerCompany?->only('id', 'name', 'slug'),
            'roles' => $this->getRoleNames(),
        ];
    }
}
