<?php

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Observers\ProgramCacheObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['program_id', 'title', 'position', 'topics'])]
#[ObservedBy(ProgramCacheObserver::class)]
class Section extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['topics' => 'array'];
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }
}
