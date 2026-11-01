<?php

namespace App\Modules\Career\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Target karier yang dipakai kuesioner MBL dan penanda program (programs.career_tracks).
 */
enum CareerTrack: string implements HasLabel
{
    case SoftwareEngineer = 'software_engineer';
    case FrontendDeveloper = 'frontend_developer';
    case UiUxDesigner = 'ui_ux_designer';
    case DigitalMarketer = 'digital_marketer';
    case DataAnalyst = 'data_analyst';
    case HrProfessional = 'hr_professional';

    public function label(): string
    {
        return match ($this) {
            self::SoftwareEngineer => 'Backend / Fullstack Developer',
            self::FrontendDeveloper => 'Frontend Developer',
            self::UiUxDesigner => 'UI/UX Designer',
            self::DigitalMarketer => 'Digital Marketer',
            self::DataAnalyst => 'Data Analyst',
            self::HrProfessional => 'HR Professional',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
