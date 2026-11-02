<?php

namespace App\Modules\Catalog;

use App\Modules\Catalog\Models\Program;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache detail program (`program:{slug}`, TTL 30 menit) sesuai blueprint §8.
 */
class ProgramCache
{
    public const TTL_SECONDS = 1800;

    /**
     * @template T
     *
     * @param  Closure(): T  $resolver
     * @return T
     */
    public static function remember(string $slug, Closure $resolver): mixed
    {
        return Cache::remember(self::key($slug), self::TTL_SECONDS, $resolver);
    }

    public static function forget(Program $program): void
    {
        Cache::forget(self::key($program->slug));

        if ($program->wasChanged('slug')) {
            Cache::forget(self::key((string) $program->getOriginal('slug')));
        }
    }

    private static function key(string $slug): string
    {
        return "program:{$slug}";
    }
}
