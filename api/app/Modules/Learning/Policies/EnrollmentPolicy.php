<?php

namespace App\Modules\Learning\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Models\Enrollment;

class EnrollmentPolicy
{
    public function view(User $user, Enrollment $enrollment): bool
    {
        return $enrollment->user_id === $user->id && $enrollment->status !== EnrollmentStatus::Refunded;
    }

    /**
     * Progres, tugas, dan diskusi hanya untuk enrollment yang masih aktif atau sudah lulus.
     */
    public function learn(User $user, Enrollment $enrollment): bool
    {
        return $enrollment->user_id === $user->id
            && in_array($enrollment->status, [EnrollmentStatus::Active, EnrollmentStatus::Completed], true);
    }
}
