<?php

namespace App\Modules\Commerce\Contracts;

use App\Modules\Commerce\Data\PaymentInstruction;
use App\Modules\Commerce\Data\PaymentNotification;
use App\Modules\Commerce\Enums\PaymentMethod;
use App\Modules\Commerce\Models\Order;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function name(): string;

    /**
     * Membuat transaksi di gateway dan mengembalikan instruksi bayar.
     */
    public function charge(Order $order, PaymentMethod $method): PaymentInstruction;

    /**
     * Memverifikasi signature webhook.
     */
    public function verify(Request $request): bool;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function parse(array $payload): PaymentNotification;

    /**
     * Menarik status terbaru dari gateway (dipakai rekonsiliasi). Null bila belum ada transaksi.
     */
    public function fetchStatus(Order $order): ?PaymentNotification;
}
