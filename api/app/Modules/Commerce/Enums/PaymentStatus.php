<?php

namespace App\Modules\Commerce\Enums;

/**
 * Status transaksi mengikuti istilah Midtrans; driver lain memetakan ke nilai ini.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Settlement = 'settlement';
    case Capture = 'capture';
    case Expire = 'expire';
    case Cancel = 'cancel';
    case Deny = 'deny';
    case Failure = 'failure';

    public function isSuccess(): bool
    {
        return in_array($this, [self::Settlement, self::Capture], true);
    }

    public function isFailure(): bool
    {
        return in_array($this, [self::Expire, self::Cancel, self::Deny, self::Failure], true);
    }
}
