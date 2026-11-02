<?php

namespace App\Modules\Catalog\Enums;

use Filament\Support\Contracts\HasLabel;

enum LessonType: string implements HasLabel
{
    case Video = 'video';
    case Article = 'article';
    case Quiz = 'quiz';
    case Assignment = 'assignment';
    case Live = 'live';

    public function label(): string
    {
        return match ($this) {
            self::Video => 'Video',
            self::Article => 'Artikel',
            self::Quiz => 'Kuis',
            self::Assignment => 'Tugas',
            self::Live => 'Live session',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
