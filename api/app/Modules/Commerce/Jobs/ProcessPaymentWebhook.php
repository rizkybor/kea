<?php

namespace App\Modules\Commerce\Jobs;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(public int $paymentId) {}

    public function handle(): void
    {
        DB::transaction(function () {
            $payment = Payment::findOrFail($this->paymentId);
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            if (! $order->isPending()) {
                return; // duplikat / terlambat
            }

            if ($payment->status->isSuccess() && $payment->amount === $order->total) {
                $order->markPaid();
            } elseif ($payment->status->isFailure()) {
                $order->markFailed();
            }
        });
    }
}
