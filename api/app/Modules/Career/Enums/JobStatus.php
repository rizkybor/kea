<?php

namespace App\Modules\Career\Enums;

use Filament\Support\Contracts\HasLabel;

enum JobStatus: string implements HasLabel
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Dibuka',
            self::Closed => 'Ditutup',
        };
    }
}
