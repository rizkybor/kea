<?php

namespace App\Modules\Career\Recommendation;

use App\Modules\Career\Enums\CareerTrack;
use App\Modules\Catalog\Enums\ProgramType;
use App\Modules\Catalog\Http\Resources\ProgramCardResource;
use App\Modules\Catalog\Models\Cohort;
use App\Modules\Catalog\Models\Program;
use Illuminate\Support\Collection;

/**
 * Rekomendasi jalur belajar MBL berbasis skor yang bisa dijelaskan: setiap poin punya alasan
 * yang ditampilkan ke learner (target karier, minat, gaya belajar, jadwal, budget, level).
 */
class CareerPathRecommender
{
    private const BUDGET_LIMIT = ['low' => 500_000, 'mid' => 3_000_000, 'high' => PHP_INT_MAX];

    private const MAX_SCORE = 110;

    /**
     * @param  array<string, mixed>  $answers  jawaban yang sudah lolos Questionnaire::rules()
     * @return array{target_track: ?string, target_label: string, tracks: list<string>, primary: ?array<string, mixed>, alternatives: list<array<string, mixed>>, warm_up: ?array<string, mixed>}
     */
    public function recommend(array $answers): array
    {
        $target = CareerTrack::tryFrom($answers['target_track']);
        // Bobot per track dari minat: track utama 25, pendukung 10 (diambil yang terbesar).
        $interestWeights = [];
        foreach ($answers['interests'] as $interest) {
            foreach (Questionnaire::INTEREST_TRACKS[$interest] ?? [] as $i => $track) {
                $interestWeights[$track->value] = max($interestWeights[$track->value] ?? 0, $i === 0 ? 25 : 10);
            }
        }
        $interestTracks = collect(array_keys($interestWeights));

        $programs = Program::published()
            ->with(['category', 'cohorts' => fn ($q) => $q->where('is_open', true)->where('starts_at', '>', now())])
            ->withCount('lessons')
            ->get();

        $scored = $programs
            ->map(fn (Program $p) => $this->score($p, $answers, $target, $interestWeights))
            ->filter()
            ->sortByDesc('score')
            ->values();

        $main = $scored->reject(fn ($r) => $r['program']->type === ProgramType::Event)->values();
        $primary = $main->first();

        // Misi cari kerja: bootcamp bergaransi jadi program utama selama skornya tidak tertinggal jauh.
        if ($primary && in_array($answers['status'], Questionnaire::JOB_SEEKING, true)) {
            $bootcamp = $main->first(fn ($r) => $r['program']->type === ProgramType::Bootcamp);
            if ($bootcamp && $bootcamp['score'] >= $primary['score'] - 15) {
                $primary = $bootcamp;
                $main = $main->sortByDesc(fn ($r) => $r === $bootcamp ? PHP_INT_MAX : $r['score'])->values();
            }
        }

        $warmUp = $primary ? $scored
            ->filter(fn ($r) => $r['program']->id !== $primary['program']->id
                && $r['program']->type !== ProgramType::Bootcamp
                && $r['program']->sellingPrice() < $primary['program']->sellingPrice())
            ->sortByDesc('score')
            ->first() : null;

        $resolvedTracks = $target ? [$target->value] : $interestTracks->all();

        return [
            'target_track' => $target?->value,
            'target_label' => $target?->label() ?? $this->inferredLabel($primary, $interestTracks),
            'tracks' => $resolvedTracks,
            'primary' => $primary ? $this->present($primary) : null,
            'alternatives' => $main->slice(1, 2)->reject(fn ($r) => $warmUp && $r['program']->id === $warmUp['program']->id)->map(fn ($r) => $this->present($r))->values()->all(),
            'warm_up' => $warmUp ? $this->present($warmUp) : null,
        ];
    }

    /**
     * @param  array<string, int>  $interestWeights
     * @return array{program: Program, score: int, reasons: list<string>, cohort: ?Cohort}|null
     */
    private function score(Program $program, array $answers, ?CareerTrack $target, array $interestWeights): ?array
    {
        $tracks = collect($program->career_tracks ?? []);
        $reasons = [];
        $score = 0;

        // 1. Relevansi karier (wajib > 0).
        $matched = $tracks->filter(fn (string $t) => isset($interestWeights[$t]));
        $interestScore = $matched->sum(fn (string $t) => $interestWeights[$t]);
        if ($target) {
            if ($tracks->contains($target->value)) {
                $score += 50;
                $reasons[] = "Menyiapkan kamu menjadi {$target->label()}";
            }
            $score += min(20, (int) round($interestScore / 2));
        } else {
            $score += min(50, $interestScore);
            if ($matched->isNotEmpty()) {
                $reasons[] = 'Sesuai minatmu: '.$matched->sortByDesc(fn (string $t) => $interestWeights[$t])->map(fn (string $t) => CareerTrack::from($t)->label())->implode(', ');
            }
        }
        if ($score === 0) {
            return null;
        }

        // 2. Program cohort harus punya kursi tersedia.
        $cohort = null;
        if ($program->type->requiresCohort()) {
            $available = $program->cohorts->filter->isAvailable();
            if ($available->isEmpty()) {
                return null;
            }
            $cohort = $answers['availability'] === 'flexible'
                ? $available->first()
                : $available->firstWhere('schedule_type', $answers['availability']);
        }

        // 3. Gaya belajar.
        $score += match ([$answers['learning_style'], $program->type]) {
            ['live', ProgramType::Bootcamp] => 15,
            ['live', ProgramType::ShortClass], ['live', ProgramType::Event] => 10,
            ['self_paced', ProgramType::VideoCourse] => 15,
            ['self_paced', ProgramType::Bootcamp] => -5,
            ['live', ProgramType::VideoCourse] => -5,
            default => 6,
        };
        if ($answers['learning_style'] === 'live' && $program->type !== ProgramType::VideoCourse) {
            $reasons[] = 'Belajar live bareng mentor praktisi';
        } elseif ($answers['learning_style'] === 'self_paced' && $program->type === ProgramType::VideoCourse) {
            $reasons[] = 'Bisa dipelajari mandiri, akses selamanya';
        }

        // 4. Jadwal.
        if ($program->type->requiresCohort()) {
            if ($cohort) {
                $score += 10;
                $reasons[] = "Ada jadwal {$cohort->name} mulai ".$cohort->starts_at->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('j F');
            } else {
                $score -= 10;
            }
        } elseif ($answers['availability'] === 'flexible') {
            $score += 10;
        }

        // 5. Budget.
        $price = $program->sellingPrice();
        if ($price <= self::BUDGET_LIMIT[$answers['budget']]) {
            $score += 10;
            $reasons[] = $price === 0 ? 'Gratis' : 'Masuk budget kamu';
        } elseif ($program->installment_from && $answers['budget'] === 'mid') {
            $score += 5;
            $reasons[] = 'Bisa dicicil mulai Rp'.number_format($program->installment_from, 0, ',', '.').'/bulan';
        } else {
            $score -= 15;
        }

        // 6. Level & tujuan kerja.
        if ($program->level === 'pemula' && $answers['experience'] !== 'related_job') {
            $score += 5;
            $reasons[] = 'Dirancang untuk pemula';
        }
        if ($program->has_job_guarantee && in_array($answers['status'], Questionnaire::JOB_SEEKING, true)) {
            $score += 15;
            $reasons[] = 'Ada garansi kerja & penyaluran ke mitra hiring';
        }

        return ['program' => $program, 'score' => $score, 'reasons' => $reasons, 'cohort' => $cohort];
    }

    /**
     * @param  array{program: Program, score: int, reasons: list<string>, cohort: ?Cohort}  $result
     * @return array<string, mixed>
     */
    private function present(array $result): array
    {
        $program = $result['program'];

        return [
            'program' => (new ProgramCardResource($program))->response()->getData(true)['data'],
            'match_pct' => max(10, min(99, (int) round($result['score'] * 100 / self::MAX_SCORE))),
            'reasons' => $result['reasons'],
            'suggested_cohort_id' => $result['cohort']?->id,
        ];
    }

    /**
     * @param  Collection<int, string>  $interestTracks
     */
    private function inferredLabel(?array $primary, Collection $interestTracks): string
    {
        $match = collect($primary['program']->career_tracks ?? [])->intersect($interestTracks)->first() ?? $interestTracks->first();

        return $match ? CareerTrack::from($match)->label() : 'Karier digital';
    }
}
