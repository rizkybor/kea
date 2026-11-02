<?php

namespace App\Modules\Identity\Enums;

enum Role: string
{
    case Learner = 'learner';
    case Mentor = 'mentor';
    case ProgramManager = 'pm';
    case Admin = 'admin';
    case Finance = 'finance';
    case Partner = 'partner';

    /**
     * @return list<string>
     */
    public static function panelRoles(): array
    {
        return [self::Admin->value, self::ProgramManager->value, self::Finance->value];
    }
}
