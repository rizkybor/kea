<?php

namespace App\Modules\Career\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Pipeline lamaran (JOB-03). `hired` menjadi dasar perhitungan placement rate & klaim garansi kerja.
 */
enum ApplicationStatus: string implements HasLabel
{
    case Applied = 'applied';
    case Reviewing = 'reviewing';
    case Interview = 'interview';
    case Offered = 'offered';
    case Hired = 'hired';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Applied => 'Dikirim',
            self::Reviewing => 'Ditinjau',
            self::Interview => 'Interview',
            self::Offered => 'Penawaran',
            self::Hired => 'Diterima',
            self::Rejected => 'Tidak lolos',
            self::Withdrawn => 'Dibatalkan',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Hired, self::Rejected, self::Withdrawn], true);
    }

    /**
     * Status yang boleh di-set oleh hiring partner / career team.
     *
     * @return list<self>
     */
    public static function pipeline(): array
    {
        return [self::Reviewing, self::Interview, self::Offered, self::Hired, self::Rejected];
    }
}
