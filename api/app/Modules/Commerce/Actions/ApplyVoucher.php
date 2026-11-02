<?php

namespace App\Modules\Commerce\Actions;

use App\Modules\Commerce\Enums\OrderStatus;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Voucher;
use App\Support\ApiException;

/**
 * Validasi voucher (PAY-02): aktif, masa berlaku, kuota, minimal transaksi, 1x per user.
 */
class ApplyVoucher
{
    public function handle(Order $order, string $code): Order
    {
        $this->ensureEditable($order);

        $voucher = Voucher::whereRaw('upper(code) = ?', [strtoupper(trim($code))])->first();

        $fail = fn (string $message) => throw new ApiException($message, 'voucher_invalid', 422, ['code' => [$message]]);

        if (! $voucher || ! $voucher->is_active) {
            $fail('Kode voucher tidak ditemukan.');
        }
        if (($voucher->starts_at && $voucher->starts_at->isFuture()) || ($voucher->ends_at && $voucher->ends_at->isPast())) {
            $fail('Voucher sudah tidak berlaku.');
        }
        if ($voucher->quota !== null && $voucher->used_count >= $voucher->quota) {
            $fail('Kuota voucher sudah habis.');
        }
        if ($order->subtotal < $voucher->min_subtotal) {
            $fail('Total belanja belum memenuhi minimal transaksi voucher.');
        }

        $usedBefore = Order::where('user_id', $order->user_id)
            ->where('voucher_id', $voucher->id)
            ->where('status', OrderStatus::Paid)
            ->exists();

        if ($usedBefore) {
            $fail('Voucher ini sudah pernah kamu pakai.');
        }

        $discount = $voucher->discountFor($order->subtotal);

        $order->update([
            'voucher_id' => $voucher->id,
            'discount' => $discount,
            'total' => $order->subtotal - $discount,
        ]);

        return $order;
    }

    public function remove(Order $order): Order
    {
        $this->ensureEditable($order);

        $order->update(['voucher_id' => null, 'discount' => 0, 'total' => $order->subtotal]);

        return $order;
    }

    private function ensureEditable(Order $order): void
    {
        if (! $order->isPending() || $order->payment_instruction !== null) {
            throw new ApiException('Voucher tidak bisa diubah setelah instruksi bayar dibuat.', 'order_locked', 409);
        }
    }
}
