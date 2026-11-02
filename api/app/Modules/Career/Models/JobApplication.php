<?php

namespace App\Modules\Career\Models;

use App\Modules\Career\Enums\ApplicationStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['job_posting_id', 'user_id', 'status', 'cover_letter', 'cv_path', 'portfolio_url', 'hired_at'])]
class JobApplication extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'hired_at' => 'datetime',
        ];
    }

    /**
     * Ubah status sekaligus mencatat riwayatnya (dasar audit klaim garansi kerja).
     */
    public function transitionTo(ApplicationStatus $status, ?User $actor, ?string $note = null): void
    {
        $this->update([
            'status' => $status,
            'hired_at' => $status === ApplicationStatus::Hired ? ($this->hired_at ?? now()) : $this->hired_at,
        ]);

        $this->events()->create([
            'status' => $status,
            'actor_id' => $actor?->id,
            'note' => $note,
            'created_at' => now(),
        ]);
    }

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return HasMany<JobApplicationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(JobApplicationEvent::class)->orderBy('created_at')->orderBy('id');
    }
}
