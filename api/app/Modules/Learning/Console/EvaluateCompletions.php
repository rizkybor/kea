<?php

namespace App\Modules\Learning\Console;

use App\Modules\Learning\Actions\EvaluateCompletion;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Models\Enrollment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('enrollments:evaluate-completion')]
#[Description('Cek syarat lulus enrollment aktif dan picu penerbitan sertifikat')]
class EvaluateCompletions extends Command
{
    public function handle(EvaluateCompletion $evaluate): int
    {
        $completed = 0;

        Enrollment::where('status', EnrollmentStatus::Active)
            ->where('progress_pct', 100)
            ->each(function (Enrollment $enrollment) use ($evaluate, &$completed) {
                $completed += (int) $evaluate->handle($enrollment);
            });

        $this->info("{$completed} enrollment lulus.");

        return self::SUCCESS;
    }
}
