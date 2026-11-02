<?php

namespace App\Modules\Certification\Listeners;

use App\Modules\Certification\Actions\IssueCertificate;
use App\Modules\Learning\Events\EnrollmentCompleted;
use App\Modules\Learning\Models\Enrollment;
use Illuminate\Contracts\Queue\ShouldQueue;

class IssueCertificateOnCompletion implements ShouldQueue
{
    public function __construct(private IssueCertificate $issue) {}

    public function handle(EnrollmentCompleted $event): void
    {
        $this->issue->handle(Enrollment::findOrFail($event->enrollmentId));
    }
}
