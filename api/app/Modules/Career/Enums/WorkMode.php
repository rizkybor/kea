<?php

namespace App\Modules\Career\Enums;

use Filament\Support\Contracts\HasLabel;

enum WorkMode: string implements HasLabel
{
    case Onsite = 'onsite';
    case Hybrid = 'hybrid';
    case Remote = 'remote';

    public function label(): string
    {
        return match ($this) {
            self::Onsite => 'On-site',
            self::Hybrid => 'Hybrid',
            self::Remote => 'Remote',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
