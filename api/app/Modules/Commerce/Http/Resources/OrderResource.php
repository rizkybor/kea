<?php

namespace App\Modules\Commerce\Http\Resources;

use App\Modules\Commerce\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status->value,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'voucher_code' => $this->whenLoaded('voucher', fn () => $this->voucher?->code),
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'payment_method' => $this->payment_method,
            'payment_instruction' => $this->payment_instruction,
            'reserved_until' => $this->reserved_until?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'program_title' => $item->program_title,
                'program_slug' => $item->program?->slug,
                'program_type' => $item->program?->type->value,
                'program_type_label' => $item->program?->type->label(),
                'thumbnail_url' => $item->program?->thumbnail_url,
                'cohort_name' => $item->cohort_name,
                'cohort_starts_at' => $item->cohort?->starts_at?->toIso8601String(),
                'price' => $item->price,
            ])),
            'simulator' => config('payment.default') === 'simulator',
        ];
    }
}
