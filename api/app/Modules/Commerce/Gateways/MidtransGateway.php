<?php

namespace App\Modules\Commerce\Gateways;

use App\Modules\Commerce\Contracts\PaymentGateway;
use App\Modules\Commerce\Data\PaymentInstruction;
use App\Modules\Commerce\Data\PaymentNotification;
use App\Modules\Commerce\Enums\PaymentMethod;
use App\Modules\Commerce\Enums\PaymentStatus;
use App\Modules\Commerce\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Midtrans Core API (VA, QRIS, GoPay) + Snap untuk kartu kredit/cicilan.
 */
class MidtransGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'midtrans';
    }

    public function charge(Order $order, PaymentMethod $method): PaymentInstruction
    {
        $order->loadMissing('items', 'user');
        $expiresAt = now()->addMinutes(config('payment.reservation_minutes'));

        $base = [
            'transaction_details' => ['order_id' => $order->uuid, 'gross_amount' => $order->total],
            'customer_details' => [
                'first_name' => $order->customer_name ?? $order->user->name,
                'email' => $order->user->email,
                'phone' => $order->customer_phone,
            ],
            'item_details' => $this->itemDetails($order),
            'custom_expiry' => [
                'order_time' => now()->format('Y-m-d H:i:s O'),
                'expiry_duration' => config('payment.reservation_minutes'),
                'unit' => 'minute',
            ],
        ];

        if ($method === PaymentMethod::CreditCard) {
            $snap = $this->client()
                ->post($this->snapUrl().'/snap/v1/transactions', $base + [
                    'enabled_payments' => ['credit_card'],
                    'credit_card' => ['secure' => true, 'installment' => ['required' => false]],
                    'expiry' => $base['custom_expiry'],
                ])
                ->throw()
                ->json();

            return new PaymentInstruction($method, 'Kartu kredit', null, null, $snap['redirect_url'], $expiresAt);
        }

        $payload = match ($method) {
            PaymentMethod::VirtualAccount => ['payment_type' => 'bank_transfer', 'bank_transfer' => ['bank' => config('payment.midtrans.va_bank')]],
            PaymentMethod::Qris => ['payment_type' => 'qris', 'qris' => ['acquirer' => 'gopay']],
            PaymentMethod::EWallet => ['payment_type' => 'gopay'],
        };

        $response = $this->client()->post($this->apiUrl().'/v2/charge', $base + $payload)->throw()->json();

        return new PaymentInstruction(
            method: $method,
            channel: match ($method) {
                PaymentMethod::VirtualAccount => strtoupper((string) config('payment.midtrans.va_bank')).' Virtual Account',
                PaymentMethod::Qris => 'QRIS',
                PaymentMethod::EWallet => 'GoPay',
            },
            vaNumber: Arr::get($response, 'va_numbers.0.va_number') ?? Arr::get($response, 'permata_va_number'),
            qrString: Arr::get($response, 'qr_string'),
            redirectUrl: $this->action($response, 'deeplink-redirect') ?? $this->action($response, 'generate-qr-code'),
            expiresAt: $expiresAt,
        );
    }

    public function verify(Request $request): bool
    {
        $expected = hash('sha512',
            $request->input('order_id').$request->input('status_code').$request->input('gross_amount').config('payment.midtrans.server_key'));

        return hash_equals($expected, (string) $request->input('signature_key'));
    }

    public function parse(array $payload): PaymentNotification
    {
        $status = PaymentStatus::tryFrom((string) ($payload['transaction_status'] ?? '')) ?? PaymentStatus::Pending;

        // Kartu kredit "capture" baru dianggap lunas bila fraud_status = accept.
        if ($status === PaymentStatus::Capture && ($payload['fraud_status'] ?? 'accept') !== 'accept') {
            $status = PaymentStatus::Pending;
        }

        return new PaymentNotification(
            orderUuid: (string) $payload['order_id'],
            reference: (string) $payload['transaction_id'],
            method: $payload['payment_type'] ?? null,
            amount: (int) round((float) ($payload['gross_amount'] ?? 0)),
            status: $status,
        );
    }

    public function fetchStatus(Order $order): ?PaymentNotification
    {
        $response = $this->client()->get($this->apiUrl()."/v2/{$order->uuid}/status");

        if ($response->notFound() || $response->json('status_code') === '404') {
            return null;
        }

        return $this->parse($response->throw()->json());
    }

    private function client(): PendingRequest
    {
        return Http::withBasicAuth((string) config('payment.midtrans.server_key'), '')
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 200, throw: false);
    }

    private function apiUrl(): string
    {
        return config('payment.midtrans.is_production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
    }

    private function snapUrl(): string
    {
        return config('payment.midtrans.is_production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
    }

    /**
     * Midtrans mewajibkan jumlah item_details = gross_amount, jadi diskon voucher dikirim sebagai item negatif.
     *
     * @return list<array{id: string, price: int, quantity: int, name: string}>
     */
    private function itemDetails(Order $order): array
    {
        $items = $order->items->map(fn ($item) => [
            'id' => (string) $item->program_id,
            'price' => $item->price,
            'quantity' => 1,
            'name' => mb_substr($item->program_title, 0, 50),
        ])->all();

        if ($order->discount > 0) {
            $items[] = ['id' => 'voucher', 'price' => -$order->discount, 'quantity' => 1, 'name' => 'Voucher'];
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function action(array $response, string $name): ?string
    {
        return collect($response['actions'] ?? [])->firstWhere('name', $name)['url'] ?? null;
    }
}
