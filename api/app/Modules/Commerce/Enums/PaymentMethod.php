<?php

namespace App\Modules\Commerce\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case VirtualAccount = 'va';
    case Qris = 'qris';
    case EWallet = 'ewallet';
    case CreditCard = 'cc';

    public function label(): string
    {
        return match ($this) {
            self::VirtualAccount => 'Virtual Account',
            self::Qris => 'QRIS',
            self::EWallet => 'E-Wallet',
            self::CreditCard => 'Kartu Kredit / Cicilan',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
