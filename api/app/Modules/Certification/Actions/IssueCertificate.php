<?php

namespace App\Modules\Certification\Actions;

use App\Modules\Certification\Models\Certificate;
use App\Modules\Learning\Enums\SubmissionStatus;
use App\Modules\Learning\Models\Enrollment;

class IssueCertificate
{
    public function handle(Enrollment $enrollment): Certificate
    {
        $enrollment->loadMissing('user', 'program', 'cohort');

        $finalScore = $enrollment->submissions()->where('status', SubmissionStatus::Reviewed)->avg('score');

        return Certificate::firstOrCreate(
            ['enrollment_id' => $enrollment->id],
            [
                'code' => $this->code($enrollment),
                'recipient_name' => $enrollment->user->name,
                'program_title' => $enrollment->program->title,
                'cohort_name' => $enrollment->cohort?->name,
                'final_score' => $finalScore !== null ? (int) round($finalScore) : null,
                'issued_at' => now(),
            ],
        );
    }

    /**
     * Format: KEA-{KODE PROGRAM}-{TAHUN}-{ID ENROLLMENT 6 DIGIT}, mis. KEA-FSWD-2026-000123.
     */
    private function code(Enrollment $enrollment): string
    {
        return sprintf('KEA-%s-%s-%06d', strtoupper($enrollment->program->code), now()->format('Y'), $enrollment->id);
    }
}
