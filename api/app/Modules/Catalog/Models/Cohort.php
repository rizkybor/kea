<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Observers\ProgramCacheObserver;
use App\Modules\Identity\Models\User;
use App\Modules\Learning\Models\Enrollment;
use App\Modules\Learning\Models\LiveSession;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['program_id', 'name', 'schedule_type', 'schedule_label', 'starts_at', 'ends_at', 'quota', 'seats_taken', 'is_open', 'community_url'])]
#[ObservedBy(ProgramCacheObserver::class)]
class Cohort extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'quota' => 'integer',
            'seats_taken' => 'integer',
            'is_open' => 'boolean',
        ];
    }

    public function seatsLeft(): int
    {
        return max(0, $this->quota - $this->seats_taken);
    }

    /**
     * Cohort bisa dibeli bila dibuka admin, belum mulai, dan kuota masih ada.
     */
    public function isAvailable(): bool
    {
        return $this->is_open && $this->seatsLeft() > 0 && $this->starts_at->isFuture();
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function mentors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cohort_mentors');
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return HasMany<LiveSession, $this>
     */
    public function liveSessions(): HasMany
    {
        return $this->hasMany(LiveSession::class)->orderBy('starts_at');
    }
}
