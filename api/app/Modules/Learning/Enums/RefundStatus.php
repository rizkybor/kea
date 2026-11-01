<?php

namespace App\Modules\Learning\Enums;

enum RefundStatus: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';
}
