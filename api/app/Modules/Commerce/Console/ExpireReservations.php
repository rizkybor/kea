<?php

namespace App\Modules\Commerce\Console;

use App\Modules\Commerce\Enums\OrderStatus;
use App\Modules\Commerce\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('orders:expire-reservations')]
#[Description('Order pending yang lewat reserved_until menjadi expired dan kuota cohort dikembalikan')]
class ExpireReservations extends Command
{
    public function handle(): int
    {
        $expired = 0;

        Order::where('status', OrderStatus::Pending)
            ->where('reserved_until', '<', now())
            ->pluck('id')
            ->each(function (int $id) use (&$expired) {
                DB::transaction(function () use ($id, &$expired) {
                    $order = Order::whereKey($id)->lockForUpdate()->first();

                    if ($order?->isPending()) {
                        $order->markExpired();
                        $expired++;
                    }
                });
            });

        $this->info("{$expired} order expired.");

        return self::SUCCESS;
    }
}
