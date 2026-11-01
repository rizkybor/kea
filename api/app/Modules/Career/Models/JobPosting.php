<?php

namespace App\Modules\Career\Models;

use App\Modules\Career\Enums\EmploymentType;
use App\Modules\Career\Enums\JobStatus;
use App\Modules\Career\Enums\WorkMode;
use App\Modules\Catalog\Models\Category;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id', 'category_id', 'posted_by', 'slug', 'title', 'employment_type', 'work_mode', 'location',
    'salary_min', 'salary_max', 'show_salary', 'description', 'requirements', 'skills', 'alumni_only',
    'status', 'published_at', 'closes_at',
])]
class JobPosting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employment_type' => EmploymentType::class,
            'work_mode' => WorkMode::class,
            'status' => JobStatus::class,
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'show_salary' => 'boolean',
            'requirements' => 'array',
            'skills' => 'array',
            'alumni_only' => 'boolean',
            'published_at' => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Lowongan tayang: status open, sudah dipublikasi, dan belum lewat batas lamar.
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', JobStatus::Open)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()));
    }

    public function isOpen(): bool
    {
        return $this->status === JobStatus::Open
            && $this->published_at?->isPast()
            && ($this->closes_at === null || $this->closes_at->isFuture());
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /**
     * @return HasMany<JobApplication, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}
