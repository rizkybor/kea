<?php

namespace App\Modules\Commerce\Gateways;

use App\Modules\Commerce\Contracts\PaymentGateway;
use App\Modules\Commerce\Data\PaymentInstruction;
use App\Modules\Commerce\Data\PaymentNotification;
use App\Modules\Commerce\Enums\PaymentMethod;
use App\Modules\Commerce\Enums\PaymentStatus;
use App\Modules\Commerce\Models\Order;
use Illuminate\Http\Request;

/**
 * Gateway palsu untuk lokal & staging: menghasilkan nomor VA/QR dummy dan
 * notifikasi bertanda tangan HMAC dengan format payload yang sama seperti Midtrans.
 */
class SimulatorGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'simulator';
    }

    public function charge(Order $order, PaymentMethod $method): PaymentInstruction
    {
        $digits = str_pad((string) (crc32($order->uuid) % 10_000_000_000), 10, '0', STR_PAD_LEFT);

        return new PaymentInstruction(
            method: $method,
            channel: match ($method) {
                PaymentMethod::VirtualAccount => 'BCA Virtual Account (simulasi)',
                PaymentMethod::Qris => 'QRIS (simulasi)',
                PaymentMethod::EWallet => 'GoPay (simulasi)',
                PaymentMethod::CreditCard => 'Kartu kredit (simulasi)',
            },
            vaNumber: $method === PaymentMethod::VirtualAccount ? '8808'.$digits : null,
            qrString: in_array($method, [PaymentMethod::Qris, PaymentMethod::EWallet], true) ? 'KEA-SIMULATOR|'.$order->uuid.'|'.$order->total : null,
            redirectUrl: null,
            expiresAt: now()->addMinutes(config('payment.reservation_minutes')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function settlementPayload(Order $order, PaymentStatus $status = PaymentStatus::Settlement): array
    {
        return [
            'order_id' => $order->uuid,
            'transaction_id' => 'SIM-'.$order->uuid,
            'payment_type' => $order->payment_method,
            'gross_amount' => (string) $order->total,
            'transaction_status' => $status->value,
        ];
    }

    public function sign(string $body): string
    {
        return hash_hmac('sha256', $body, (string) config('app.key'));
    }

    public function verify(Request $request): bool
    {
        return hash_equals($this->sign($request->getContent()), (string) $request->header('X-Simulator-Signature'));
    }

    public function parse(array $payload): PaymentNotification
    {
        return new PaymentNotification(
            orderUuid: (string) $payload['order_id'],
            reference: (string) $payload['transaction_id'],
            method: $payload['payment_type'] ?? null,
            amount: (int) $payload['gross_amount'],
            status: PaymentStatus::tryFrom((string) $payload['transaction_status']) ?? PaymentStatus::Pending,
        );
    }

    public function fetchStatus(Order $order): ?PaymentNotification
    {
        return null;
    }
}
