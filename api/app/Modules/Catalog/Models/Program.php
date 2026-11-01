<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\ProgramType;
use App\Modules\Catalog\Observers\ProgramCacheObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id', 'type', 'code', 'slug', 'title', 'summary', 'description', 'level', 'duration_label', 'format_label',
    'price', 'promo_price', 'installment_from', 'has_job_guarantee', 'thumbnail_url', 'preview_video_url',
    'benefits', 'faqs', 'rating', 'rating_count', 'enrolled_count', 'is_published',
])]
#[ObservedBy(ProgramCacheObserver::class)]
class Program extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProgramType::class,
            'price' => 'integer',
            'promo_price' => 'integer',
            'installment_from' => 'integer',
            'has_job_guarantee' => 'boolean',
            'benefits' => 'array',
            'faqs' => 'array',
            'rating' => 'float',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Harga yang benar-benar dibayar (promo bila ada).
     */
    public function sellingPrice(): int
    {
        return $this->promo_price ?? $this->price;
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Cohort, $this>
     */
    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class)->orderBy('starts_at');
    }

    /**
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position');
    }

    /**
     * @return HasManyThrough<Lesson, Section, $this>
     */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Section::class);
    }

    /**
     * @return HasMany<Testimonial, $this>
     */
    public function testimonials(): HasMany
    {
        return $this->hasMany(Testimonial::class);
    }
}
