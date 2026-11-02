<?php

namespace App\Modules\Career\Actions;

use App\Modules\Career\Enums\ApplicationStatus;
use App\Modules\Career\Models\JobApplication;
use App\Modules\Career\Models\JobPosting;
use App\Modules\Identity\Models\User;
use App\Support\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * JOB-01: melamar pakai profil + CV. CV di-snapshot supaya perubahan CV berikutnya tidak mengubah lamaran.
 */
class ApplyToJob
{
    public function handle(User $user, JobPosting $job, ?string $coverLetter, ?string $portfolioUrl): JobApplication
    {
        if (! $job->isOpen()) {
            throw new ApiException('Lowongan ini sudah ditutup.', 'job_closed', 409);
        }
        if (! $user->cv_path || ! Storage::disk('local')->exists($user->cv_path)) {
            throw new ApiException('Unggah CV di Profil Karier sebelum melamar.', 'cv_required', 422);
        }
        if ($job->alumni_only && ! $user->isAlumni()) {
            throw new ApiException('Lowongan ini khusus alumni KEA yang sudah lulus program.', 'alumni_only', 403);
        }
        if (JobApplication::where('job_posting_id', $job->id)->where('user_id', $user->id)->exists()) {
            throw new ApiException('Kamu sudah melamar lowongan ini.', 'already_applied', 409);
        }

        return DB::transaction(function () use ($user, $job, $coverLetter, $portfolioUrl) {
            $application = JobApplication::create([
                'job_posting_id' => $job->id,
                'user_id' => $user->id,
                'status' => ApplicationStatus::Applied,
                'cover_letter' => $coverLetter,
                'portfolio_url' => $portfolioUrl ?? $user->portfolio_url,
            ]);

            $snapshot = "applications/{$application->id}/cv.pdf";
            Storage::disk('local')->copy($user->cv_path, $snapshot);
            $application->update(['cv_path' => $snapshot]);

            $application->events()->create(['status' => ApplicationStatus::Applied, 'actor_id' => $user->id, 'created_at' => now()]);

            return $application;
        });
    }
}
