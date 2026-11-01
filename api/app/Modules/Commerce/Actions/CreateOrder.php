<?php

namespace App\Modules\Commerce\Actions;

use App\Modules\Catalog\Models\Cohort;
use App\Modules\Catalog\Models\Program;
use App\Modules\Commerce\Enums\OrderStatus;
use App\Modules\Commerce\Models\Order;
use App\Modules\Identity\Models\User;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Models\Enrollment;
use App\Support\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Membuat order pending dan mereservasi satu kursi cohort selama 30 menit.
 */
class CreateOrder
{
    public function handle(User $user, Program $program, ?Cohort $cohort): Order
    {
        if (! $user->hasVerifiedEmail()) {
            throw ApiException::emailUnverified();
        }

        if (! $program->is_published) {
            throw new ApiException('Program tidak tersedia.', 'program_unavailable', 404);
        }

        if ($program->type->requiresCohort() && ! $cohort) {
            throw new ApiException('Pilih cohort terlebih dulu.', 'cohort_required', 422, ['cohort_id' => ['Cohort wajib dipilih.']]);
        }

        if ($cohort && $cohort->program_id !== $program->id) {
            throw new ApiException('Cohort tidak sesuai program.', 'cohort_mismatch', 422, ['cohort_id' => ['Cohort tidak valid.']]);
        }

        $alreadyEnrolled = Enrollment::where('user_id', $user->id)
            ->where('program_id', $program->id)
            ->where('cohort_id', $cohort?->id)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::Completed])
            ->exists();

        if ($alreadyEnrolled) {
            throw new ApiException('Kamu sudah terdaftar di program ini.', 'already_enrolled', 409);
        }

        return DB::transaction(function () use ($user, $program, $cohort) {
            $existing = Order::where('user_id', $user->id)
                ->where('status', OrderStatus::Pending)
                ->where('reserved_until', '>', now())
                ->whereHas('items', fn ($q) => $q->where('program_id', $program->id)->where('cohort_id', $cohort?->id))
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            if ($cohort) {
                $locked = Cohort::whereKey($cohort->id)->lockForUpdate()->firstOrFail();

                if (! $locked->isAvailable()) {
                    throw ApiException::quotaFull();
                }

                $locked->increment('seats_taken');
            }

            $price = $program->sellingPrice();

            $order = Order::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->id,
                'invoice_number' => $this->invoiceNumber(),
                'subtotal' => $price,
                'discount' => 0,
                'total' => $price,
                'status' => OrderStatus::Pending,
                'customer_name' => $user->name,
                'customer_phone' => $user->phone,
                'reserved_until' => now()->addMinutes(config('payment.reservation_minutes')),
            ]);

            $order->items()->create([
                'program_id' => $program->id,
                'cohort_id' => $cohort?->id,
                'program_title' => $program->title,
                'cohort_name' => $cohort?->name,
                'price' => $price,
            ]);

            return $order;
        });
    }

    private function invoiceNumber(): string
    {
        return 'INV/'.now()->format('Ymd').'/'.strtoupper(Str::random(8));
    }
}
