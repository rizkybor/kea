<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Enums\ProgramType;
use App\Modules\Catalog\Http\Resources\ProgramCardResource;
use App\Modules\Catalog\Http\Resources\ProgramDetailResource;
use App\Modules\Catalog\Models\Cohort;
use App\Modules\Catalog\Models\Program;
use App\Modules\Catalog\ProgramCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProgramController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::enum(ProgramType::class)],
            'category' => ['nullable', 'array'],
            'category.*' => ['string'],
            'schedule' => ['nullable', Rule::in(['weekday', 'weekend'])],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', Rule::in(['start', 'price', 'popular'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $programs = Program::query()
            ->published()
            ->with(['category', 'cohorts' => fn (HasMany $q) => $this->upcoming($q)])
            ->withCount('lessons')
            ->when($filters['type'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($filters['category'] ?? null, fn (Builder $q, array $slugs) => $q->whereHas('category', fn (Builder $c) => $c->whereIn('slug', $slugs)))
            ->when($filters['schedule'] ?? null, fn (Builder $q, string $schedule) => $q->whereHas('cohorts', fn (Builder $c) => $this->upcoming($c)->where('schedule_type', $schedule)))
            ->when($filters['q'] ?? null, fn (Builder $q, string $term) => $q->where(fn (Builder $w) => $w
                ->where('title', 'ilike', "%{$term}%")
                ->orWhere('summary', 'ilike', "%{$term}%")))
            ->tap(fn (Builder $q) => match ($filters['sort'] ?? 'start') {
                'price' => $q->orderByRaw('coalesce(promo_price, price) asc'),
                'popular' => $q->orderByDesc('enrolled_count'),
                default => $q->orderBy(
                    Cohort::select('starts_at')->whereColumn('cohorts.program_id', 'programs.id')
                        ->where('is_open', true)->where('starts_at', '>', now())->orderBy('starts_at')->limit(1)
                )->orderBy('title'),
            })
            ->paginate($filters['per_page'] ?? 9)
            ->withQueryString();

        return ProgramCardResource::collection($programs);
    }

    public function show(string $slug): JsonResponse
    {
        $data = ProgramCache::remember($slug, function () use ($slug) {
            $program = Program::published()
                ->where('slug', $slug)
                ->with([
                    'category',
                    'sections.lessons',
                    'testimonials',
                    'cohorts' => fn (HasMany $q) => $q->where('starts_at', '>', now()->subDays(60))->with('mentors'),
                ])
                ->withCount('lessons')
                ->firstOrFail();

            // Cache array JSON murni, bukan objek Resource (nested collection tidak aman di-serialize).
            return (new ProgramDetailResource($program))->response()->getData(true)['data'];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * @template TBuilder of Builder|HasMany
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    private function upcoming(Builder|HasMany $query): Builder|HasMany
    {
        return $query->where('is_open', true)->where('starts_at', '>', now());
    }
}
