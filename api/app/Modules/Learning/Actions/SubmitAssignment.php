<?php

namespace App\Modules\Learning\Actions;

use App\Modules\Catalog\Models\Lesson;
use App\Modules\Learning\Enums\SubmissionStatus;
use App\Modules\Learning\Models\Enrollment;
use App\Modules\Learning\Models\Submission;
use App\Support\ApiException;
use Illuminate\Http\UploadedFile;

class SubmitAssignment
{
    public function handle(Enrollment $enrollment, Lesson $lesson, ?string $linkUrl, ?UploadedFile $file, ?string $note): Submission
    {
        if (! $lesson->hasAssignment()) {
            throw new ApiException('Lesson ini tidak punya tugas.', 'no_assignment', 422);
        }

        $submission = Submission::firstOrNew(['enrollment_id' => $enrollment->id, 'lesson_id' => $lesson->id]);

        if ($submission->exists && $submission->status === SubmissionStatus::Reviewed) {
            throw new ApiException('Tugas sudah dinilai dan tidak bisa dikirim ulang.', 'already_reviewed', 409);
        }

        if ($file) {
            // Bucket private; nama file acak (blueprint §9).
            $submission->file_path = $file->store("submissions/{$enrollment->id}", 'local');
            $submission->file_name = $file->getClientOriginalName();
        }

        $submission->fill([
            'link_url' => $linkUrl,
            'note' => $note,
            'status' => SubmissionStatus::Submitted,
            'score' => null,
            'submitted_at' => now(),
        ])->save();

        return $submission;
    }
}
