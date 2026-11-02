<?php

namespace App\Filament\Widgets;

use App\Modules\Career\Enums\ApplicationStatus;
use App\Modules\Career\Enums\JobStatus;
use App\Modules\Career\Models\JobApplication;
use App\Modules\Career\Models\JobPosting;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Models\Enrollment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * KPI outcome: placement rate 6 bulan = alumni yang diterima kerja ≤ 6 bulan setelah lulus / alumni yang lulus ≥ 6 bulan lalu.
 */
class PlacementOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $cohortAlumni = Enrollment::where('status', EnrollmentStatus::Completed)
            ->where('completed_at', '<=', now()->subMonths(6))
            ->pluck('completed_at', 'user_id');

        $placed = JobApplication::where('status', ApplicationStatus::Hired)
            ->whereIn('user_id', $cohortAlumni->keys())
            ->get(['user_id', 'hired_at'])
            ->filter(fn ($a) => $a->hired_at && $a->hired_at->lte($cohortAlumni[$a->user_id]->copy()->addMonths(6)))
            ->unique('user_id')
            ->count();

        return [
            Stat::make('Placement rate 6 bulan', $cohortAlumni->isEmpty() ? '-' : round($placed * 100 / $cohortAlumni->count()).'%')
                ->description($cohortAlumni->isEmpty() ? 'Belum ada alumni ≥ 6 bulan' : "{$placed} dari {$cohortAlumni->count()} alumni"),
            Stat::make('Lowongan dibuka', number_format(JobPosting::where('status', JobStatus::Open)->count())),
            Stat::make('Kandidat di tahap interview', number_format(JobApplication::whereIn('status', [ApplicationStatus::Interview, ApplicationStatus::Offered])->count())),
            Stat::make('Diterima kerja (total)', number_format(JobApplication::where('status', ApplicationStatus::Hired)->distinct('user_id')->count('user_id'))),
        ];
    }
}
