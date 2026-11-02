<?php

namespace App\Modules\Commerce\Data;

use App\Modules\Commerce\Enums\PaymentStatus;

/**
 * Notifikasi pembayaran yang sudah dinormalisasi dari payload gateway.
 */
final readonly class PaymentNotification
{
    public function __construct(
        public string $orderUuid,
        public string $reference,
        public ?string $method,
        public int $amount,
        public PaymentStatus $status,
    ) {}
}
