<?php

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Data\PaymentNotification;
use App\Modules\Commerce\Jobs\ProcessPaymentWebhook;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Payment;

/**
 * Menyimpan event pembayaran secara idempotent (unique gateway + gateway_ref) lalu memprosesnya di queue critical.
 */
class RecordPaymentNotification
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $gateway, PaymentNotification $notification, array $payload): ?Payment
    {
        $orderId = Order::where('uuid', $notification->orderUuid)->value('id');

        if ($orderId === null) {
            return null;
        }

        $payment = Payment::updateOrCreate(
            ['gateway' => $gateway, 'gateway_ref' => $notification->reference],
            [
                'order_id' => $orderId,
                'method' => $notification->method,
                'amount' => $notification->amount,
                'status' => $notification->status,
                'payload' => $payload,
            ],
        );

        ProcessPaymentWebhook::dispatch($payment->id)->onQueue('critical');

        return $payment;
    }
}
