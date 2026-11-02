<?php

namespace App\Modules\Catalog\Http\Resources;

use App\Modules\Catalog\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Testimonial
 */
class TestimonialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'role' => $this->role,
            'company' => $this->company,
            'quote' => $this->quote,
            'avatar_url' => $this->avatar_url,
        ];
    }
}
