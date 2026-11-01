<?php

namespace App\Modules\Learning\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['lesson_id', 'cohort_id', 'user_id', 'parent_id', 'body'])]
class Discussion extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return HasMany<Discussion, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Discussion::class, 'parent_id')->oldest();
    }
}
