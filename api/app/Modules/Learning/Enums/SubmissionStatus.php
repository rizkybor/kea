<?php

namespace App\Modules\Learning\Enums;

enum SubmissionStatus: string
{
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Revision = 'revision';
}
