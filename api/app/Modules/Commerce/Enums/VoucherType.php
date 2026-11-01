<?php

namespace App\Modules\Commerce\Enums;

enum VoucherType: string
{
    case Percent = 'percent';
    case Fixed = 'fixed';
}
