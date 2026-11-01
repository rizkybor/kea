<?php

namespace App\Modules\Learning\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class EnrollmentCompleted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $enrollmentId) {}
}
