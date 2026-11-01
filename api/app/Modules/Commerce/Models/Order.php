<?php

namespace App\Modules\Commerce\Models;

use App\Modules\Catalog\Models\Cohort;
use App\Modules\Commerce\Enums\OrderStatus;
use App\Modules\Commerce\Events\OrderPaid;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'uuid', 'user_id', 'voucher_id', 'invoice_number', 'subtotal', 'discount', 'total', 'status',
    'customer_name', 'customer_phone', 'payment_method', 'payment_instruction', 'reserved_until', 'paid_at',
])]
class Order extends Model
{
    public const RESERVATION_MINUTES = 30;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'payment_instruction' => 'array',
            'reserved_until' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::Pending;
    }

    /**
     * Dipanggil di dalam transaksi dengan row order sudah di-lock.
     */
    public function markPaid(): void
    {
        $this->forceFill(['status' => OrderStatus::Paid, 'paid_at' => now(), 'reserved_until' => null])->save();
        $this->voucher?->increment('used_count');

        OrderPaid::dispatch($this->id);
    }

    public function markFailed(): void
    {
        $this->close(OrderStatus::Failed);
    }

    public function markExpired(): void
    {
        $this->close(OrderStatus::Expired);
    }

    /**
     * Menutup order yang gagal/expired dan mengembalikan kursi cohort yang direservasi.
     */
    private function close(OrderStatus $status): void
    {
        $this->forceFill(['status' => $status, 'reserved_until' => null])->save();

        foreach ($this->items()->whereNotNull('cohort_id')->pluck('cohort_id') as $cohortId) {
            Cohort::whereKey($cohortId)->where('seats_taken', '>', 0)->decrement('seats_taken');
            Cohort::find($cohortId)?->touch();
        }
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Voucher, $this>
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
