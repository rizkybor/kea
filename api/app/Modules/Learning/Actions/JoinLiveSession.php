<?php

namespace App\Modules\Learning\Actions;

use App\Modules\Learning\Models\Attendance;
use App\Modules\Learning\Models\Enrollment;
use App\Modules\Learning\Models\LiveSession;
use App\Support\ApiException;

class JoinLiveSession
{
    /**
     * Mencatat kehadiran dan mengembalikan link Zoom; hanya aktif H-15 menit sampai sesi selesai.
     */
    public function handle(Enrollment $enrollment, LiveSession $session): string
    {
        if (! $session->isJoinable() || ! $session->join_url) {
            throw new ApiException(
                'Link aktif 15 menit sebelum sesi dimulai.',
                'session_not_open',
                423,
            );
        }

        Attendance::firstOrCreate(
            ['enrollment_id' => $enrollment->id, 'live_session_id' => $session->id],
            ['joined_at' => now()],
        );

        return $session->join_url;
    }
}
