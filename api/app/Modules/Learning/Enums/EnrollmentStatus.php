<?php

namespace App\Modules\Learning\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
