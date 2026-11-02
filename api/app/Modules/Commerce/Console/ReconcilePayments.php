<?php

namespace App\Modules\Commerce\Console;

use App\Modules\Commerce\Actions\RecordPaymentNotification;
use App\Modules\Commerce\Enums\OrderStatus;
use App\Modules\Commerce\Gateways\PaymentGatewayResolver;
use App\Modules\Commerce\Models\Order;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('payments:reconcile')]
#[Description('Menarik status order pending dari gateway untuk menutup webhook yang hilang')]
class ReconcilePayments extends Command
{
    public function handle(PaymentGatewayResolver $gateways, RecordPaymentNotification $record): int
    {
        $gateway = $gateways->default();

        Order::where('status', OrderStatus::Pending)
            ->whereNotNull('payment_instruction')
            ->where('created_at', '>', now()->subDay())
            ->each(function (Order $order) use ($gateway, $record) {
                try {
                    $notification = $gateway->fetchStatus($order);
                } catch (Throwable $e) {
                    report($e);

                    return;
                }

                if ($notification) {
                    $record->handle($gateway->name(), $notification, ['source' => 'reconcile']);
                }
            });

        return self::SUCCESS;
    }
}
