<?php

namespace App\Modules\Commerce\Data;

use App\Modules\Commerce\Enums\PaymentMethod;
use Carbon\CarbonInterface;

/**
 * Instruksi bayar yang ditampilkan ke learner (nomor VA, QR, atau link redirect).
 */
final readonly class PaymentInstruction
{
    public function __construct(
        public PaymentMethod $method,
        public string $channel,
        public ?string $vaNumber,
        public ?string $qrString,
        public ?string $redirectUrl,
        public CarbonInterface $expiresAt,
    ) {}

    /**
     * @return array{method: string, method_label: string, channel: string, va_number: ?string, qr_string: ?string, redirect_url: ?string, expires_at: string}
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method->value,
            'method_label' => $this->method->label(),
            'channel' => $this->channel,
            'va_number' => $this->vaNumber,
            'qr_string' => $this->qrString,
            'redirect_url' => $this->redirectUrl,
            'expires_at' => $this->expiresAt->toIso8601String(),
        ];
    }
}
