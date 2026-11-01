<?php

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Enums\PaymentMethod;
use App\Modules\Commerce\Gateways\PaymentGatewayResolver;
use App\Modules\Commerce\Models\Order;
use App\Support\ApiException;
use Illuminate\Support\Facades\DB;

/**
 * Membuat transaksi di gateway. Idempotent: bila instruksi sudah ada, dikembalikan apa adanya.
 */
class ChargeOrder
{
    public function __construct(private PaymentGatewayResolver $gateways) {}

    public function handle(Order $order, PaymentMethod $method, string $customerName, string $customerPhone): Order
    {
        if (! $order->isPending() || $order->reserved_until?->isPast()) {
            throw new ApiException('Order sudah tidak bisa dibayar. Silakan buat order baru.', 'order_not_payable', 409);
        }

        if ($order->payment_instruction !== null) {
            return $order;
        }

        $order->update(['customer_name' => $customerName, 'customer_phone' => $customerPhone, 'payment_method' => $method->value]);

        // Voucher 100%: tidak perlu ke gateway.
        if ($order->total === 0) {
            DB::transaction(function () use ($order) {
                Order::whereKey($order->id)->lockForUpdate()->firstOrFail()->markPaid();
            });

            return $order->refresh();
        }

        $instruction = $this->gateways->default()->charge($order, $method);

        $order->update(['payment_instruction' => $instruction->toArray()]);

        return $order;
    }
}
