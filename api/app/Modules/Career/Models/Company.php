<?php

namespace App\Modules\Career\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'industry', 'city', 'website', 'logo_url', 'description', 'is_verified'])]
class Company extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_verified' => 'boolean'];
    }

    /**
     * @return HasMany<JobPosting, $this>
     */
    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    /**
     * Akun hiring partner yang mewakili perusahaan.
     *
     * @return HasMany<User, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
