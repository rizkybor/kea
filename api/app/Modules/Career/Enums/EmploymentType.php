<?php

namespace App\Modules\Career\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmploymentType: string implements HasLabel
{
    case FullTime = 'full_time';
    case Contract = 'contract';
    case Internship = 'internship';
    case PartTime = 'part_time';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Full-time',
            self::Contract => 'Kontrak',
            self::Internship => 'Magang',
            self::PartTime => 'Part-time',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
