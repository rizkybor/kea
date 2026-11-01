<?php

namespace App\Modules\Learning\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Learning\Enums\SubmissionStatus;
use App\Modules\Learning\Models\Submission;

class GradeSubmission
{
    public function __construct(private EvaluateCompletion $evaluate) {}

    public function handle(Submission $submission, User $mentor, int $score, ?string $feedback, SubmissionStatus $status): Submission
    {
        $submission->update([
            'score' => $score,
            'feedback' => $feedback,
            'status' => $status,
            'graded_by' => $mentor->id,
            'graded_at' => now(),
        ]);

        $this->evaluate->handle($submission->enrollment);

        return $submission;
    }
}
