<?php

namespace Database\Seeders;

use App\Modules\Commerce\Enums\VoucherType;
use App\Modules\Commerce\Models\Voucher;
use Illuminate\Database\Seeder;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        Voucher::create(['code' => 'KEAHEMAT', 'type' => VoucherType::Percent, 'value' => 10, 'max_discount' => 500_000, 'quota' => 500, 'ends_at' => now()->addMonths(3)]);
        Voucher::create(['code' => 'KEA250K', 'type' => VoucherType::Fixed, 'value' => 250_000, 'min_subtotal' => 2_000_000, 'quota' => 100, 'ends_at' => now()->addMonths(1)]);
        Voucher::create(['code' => 'EXPIRED', 'type' => VoucherType::Percent, 'value' => 20, 'ends_at' => now()->subDay()]);
    }
}
