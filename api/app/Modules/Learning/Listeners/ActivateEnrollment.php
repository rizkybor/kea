<?php

namespace App\Modules\Learning\Listeners;

use App\Modules\Commerce\Events\OrderPaid;
use App\Modules\Commerce\Models\Order;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Models\Enrollment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

class ActivateEnrollment implements ShouldQueue
{
    public string $queue = 'critical';

    public function handle(OrderPaid $event): void
    {
        $order = Order::with('items.program')->findOrFail($event->orderId);

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $enrollment = Enrollment::firstOrCreate(
                    ['order_item_id' => $item->id],
                    [
                        'user_id' => $order->user_id,
                        'program_id' => $item->program_id,
                        'cohort_id' => $item->cohort_id,
                        'status' => EnrollmentStatus::Active,
                    ],
                );

                if ($enrollment->wasRecentlyCreated) {
                    $item->program->increment('enrolled_count');
                }
            }
        });
    }
}
