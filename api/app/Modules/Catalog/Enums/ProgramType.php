<?php

namespace App\Modules\Catalog\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProgramType: string implements HasLabel
{
    case Bootcamp = 'bootcamp';
    case VideoCourse = 'video_course';
    case ShortClass = 'short_class';
    case Event = 'event';

    public function label(): string
    {
        return match ($this) {
            self::Bootcamp => 'Bootcamp',
            self::VideoCourse => 'Video Course',
            self::ShortClass => 'Short Class',
            self::Event => 'Event',
        };
    }

    /**
     * Program live (bootcamp, short class, event) dijual per cohort; video course tidak.
     */
    public function requiresCohort(): bool
    {
        return $this !== self::VideoCourse;
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
