<?php

namespace App\Modules\Learning\Models;

use App\Modules\Catalog\Models\Cohort;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Identity\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['cohort_id', 'lesson_id', 'mentor_id', 'title', 'starts_at', 'ends_at', 'join_url', 'recording_url'])]
class LiveSession extends Model
{
    /**
     * Link Zoom baru aktif H-15 menit (LMS-03).
     */
    public const JOIN_WINDOW_MINUTES = 15;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function joinOpensAt(): CarbonInterface
    {
        return $this->starts_at->copy()->subMinutes(self::JOIN_WINDOW_MINUTES);
    }

    public function isJoinable(): bool
    {
        return now()->between($this->joinOpensAt(), $this->ends_at);
    }

    /**
     * @return BelongsTo<Cohort, $this>
     */
    public function cohort(): BelongsTo
    {
        return $this->belongsTo(Cohort::class);
    }

    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
