<?php

namespace App\Filament\Widgets;

use App\Modules\Commerce\Enums\OrderStatus;
use App\Modules\Commerce\Models\Order;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Models\Enrollment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * ADM-03: revenue, order per status, dan konversi 30 hari terakhir.
 */
class SalesOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $since = now()->subDays(30);
        $orders = Order::where('created_at', '>=', $since);

        $byStatus = (clone $orders)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $revenue = (int) Order::where('status', OrderStatus::Paid)->where('paid_at', '>=', $since)->sum('total');
        $paid = (int) ($byStatus[OrderStatus::Paid->value] ?? 0);
        $all = (int) $byStatus->sum();

        return [
            Stat::make('Revenue 30 hari', 'Rp'.number_format($revenue, 0, ',', '.')),
            Stat::make('Order lunas', number_format($paid))
                ->description(($byStatus[OrderStatus::Pending->value] ?? 0).' pending · '.($byStatus[OrderStatus::Expired->value] ?? 0).' expired'),
            Stat::make('Konversi order → lunas', $all ? round($paid * 100 / $all).'%' : '-'),
            Stat::make('Learner aktif', number_format(Enrollment::where('status', EnrollmentStatus::Active)->count())),
        ];
    }
}
