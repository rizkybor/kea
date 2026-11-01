<?php

namespace Database\Seeders;

use App\Modules\Catalog\Enums\LessonType;
use App\Modules\Catalog\Models\Cohort;
use App\Modules\Catalog\Models\Lesson;
use App\Modules\Catalog\Models\Program;
use App\Modules\Certification\Models\Certificate;
use App\Modules\Commerce\Enums\OrderStatus;
use App\Modules\Commerce\Models\Order;
use App\Modules\Identity\Models\User;
use App\Modules\Learning\Actions\RecalculateProgress;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Enums\SubmissionStatus;
use App\Modules\Learning\Models\Attendance;
use App\Modules\Learning\Models\Discussion;
use App\Modules\Learning\Models\Enrollment;
use App\Modules\Learning\Models\LessonProgress;
use App\Modules\Learning\Models\LiveSession;
use App\Modules\Learning\Models\Submission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LearningSeeder extends Seeder
{
    public function __construct(private RecalculateProgress $recalculate) {}

    public function run(): void
    {
        $learner = User::where('email', 'learner@kea.test')->firstOrFail();
        $alumni = User::where('email', 'alumni@kea.test')->firstOrFail();
        $mentor = User::where('email', 'mentor@kea.test')->firstOrFail();

        $fullstack = Program::where('slug', 'fullstack-web-development')->firstOrFail();
        $seo = Program::where('slug', 'seo-fundamental')->firstOrFail();
        $runningCohort = $fullstack->cohorts()->where('name', 'Batch 11 · Weekday')->firstOrFail();

        $this->seedLiveSessions($runningCohort, $mentor);

        // Learner aktif: bootcamp yang sedang berjalan.
        $active = $this->enroll($learner, $fullstack, $runningCohort);
        $lessons = $this->orderedLessons($fullstack);
        foreach ($lessons->take(8) as $lesson) {
            LessonProgress::create(['enrollment_id' => $active->id, 'lesson_id' => $lesson->id, 'completed_at' => now()->subDays(30 - $lesson->id % 20)]);
        }
        $active->update(['last_lesson_id' => $lessons[8]->id]);

        $assignments = $lessons->whereNotNull('assignment_title')->values();
        Submission::create([
            'enrollment_id' => $active->id, 'lesson_id' => $assignments[0]->id, 'link_url' => 'https://github.com/sari/landing-page',
            'status' => SubmissionStatus::Reviewed, 'score' => 88, 'feedback' => 'Struktur HTML rapi dan aksesibel. Perhatikan kontras warna tombol sekunder.',
            'graded_by' => $mentor->id, 'graded_at' => now()->subDays(20), 'submitted_at' => now()->subDays(25),
        ]);
        Submission::create([
            'enrollment_id' => $active->id, 'lesson_id' => $assignments[1]->id, 'link_url' => 'https://github.com/sari/nuxt-katalog',
            'status' => SubmissionStatus::Submitted, 'submitted_at' => now()->subDays(2),
        ]);
        foreach (LiveSession::where('cohort_id', $runningCohort->id)->where('starts_at', '<', now())->get() as $session) {
            Attendance::create(['enrollment_id' => $active->id, 'live_session_id' => $session->id, 'joined_at' => $session->starts_at]);
        }
        $this->recalculate->handle($active);

        Discussion::create([
            'lesson_id' => $lessons[8]->id, 'cohort_id' => $runningCohort->id, 'user_id' => $learner->id,
            'body' => 'Kalau relasi many-to-many butuh kolom tambahan di pivot, sebaiknya pakai model pivot sendiri atau cukup withPivot?',
        ])->replies()->create([
            'lesson_id' => $lessons[8]->id, 'cohort_id' => $runningCohort->id, 'user_id' => $mentor->id,
            'body' => 'Kalau kolomnya punya logika sendiri (status, timestamp), buat model pivot. Kalau cuma data tambahan, withPivot sudah cukup.',
        ]);

        // Learner aktif: video course.
        $video = $this->enroll($learner, $seo, null);
        foreach ($this->orderedLessons($seo)->take(4) as $lesson) {
            LessonProgress::create(['enrollment_id' => $video->id, 'lesson_id' => $lesson->id, 'completed_at' => now()->subDays(3)]);
        }
        $this->recalculate->handle($video);

        // Alumni: sudah lulus + sertifikat (kode sama seperti contoh di wireframe).
        $pastCohort = $fullstack->cohorts()->create([
            'name' => 'Batch 10 · Weekday', 'schedule_type' => 'weekday', 'schedule_label' => 'Senin & Kamis, 19.00–21.00 WIB',
            'starts_at' => now()->subMonths(6), 'ends_at' => now()->subMonths(2), 'quota' => 30, 'seats_taken' => 30, 'is_open' => false,
        ]);
        $done = $this->enroll($alumni, $fullstack, $pastCohort);
        foreach ($lessons as $lesson) {
            LessonProgress::create(['enrollment_id' => $done->id, 'lesson_id' => $lesson->id, 'completed_at' => now()->subMonths(3)]);
        }
        foreach ($assignments as $i => $lesson) {
            Submission::create([
                'enrollment_id' => $done->id, 'lesson_id' => $lesson->id, 'link_url' => 'https://github.com/bima/project-'.($i + 1),
                'status' => SubmissionStatus::Reviewed, 'score' => [86, 90, 84, 92][$i] ?? 85, 'graded_by' => $mentor->id,
                'graded_at' => now()->subMonths(3), 'submitted_at' => now()->subMonths(3),
            ]);
        }
        $done->update(['status' => EnrollmentStatus::Completed, 'progress_pct' => 100, 'completed_at' => now()->subMonths(2)]);
        Certificate::create([
            'enrollment_id' => $done->id,
            'code' => 'KEA-FSWD-2026-000123',
            'recipient_name' => $alumni->name,
            'program_title' => $fullstack->title,
            'cohort_name' => $pastCohort->name,
            'final_score' => 88,
            'issued_at' => now()->subMonths(2),
        ]);
    }

    private function seedLiveSessions(Cohort $cohort, User $mentor): void
    {
        $liveLessons = Lesson::whereHas('section', fn ($q) => $q->where('program_id', $cohort->program_id))
            ->where('type', LessonType::Live)
            ->get();

        // Dua sesi sudah lewat, satu sesi berikutnya 2 hari lagi (tombol Zoom masih nonaktif).
        $starts = [now()->subDays(20), now()->subDays(6), now()->addDays(2)->setTime(12, 0)];

        foreach ($liveLessons as $i => $lesson) {
            $startsAt = $starts[$i] ?? now()->addDays(7 * $i);
            LiveSession::create([
                'cohort_id' => $cohort->id,
                'lesson_id' => $lesson->id,
                'mentor_id' => $mentor->id,
                'title' => 'Sesi '.($i + 5).' · '.$lesson->title,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addHours(2),
                'join_url' => 'https://zoom.us/j/0000000000',
                'recording_url' => $startsAt->isPast() ? 'https://example.com/rekaman/'.$lesson->id : null,
            ]);
        }
    }

    private function enroll(User $user, Program $program, ?Cohort $cohort): Enrollment
    {
        $price = $program->sellingPrice();
        $order = Order::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'invoice_number' => 'INV/SEED/'.strtoupper(Str::random(8)),
            'subtotal' => $price,
            'total' => $price,
            'status' => OrderStatus::Paid,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'payment_method' => 'va',
            'paid_at' => now()->subDays(40),
        ]);
        $item = $order->items()->create([
            'program_id' => $program->id, 'cohort_id' => $cohort?->id, 'program_title' => $program->title,
            'cohort_name' => $cohort?->name, 'price' => $price,
        ]);

        return Enrollment::create([
            'user_id' => $user->id, 'program_id' => $program->id, 'cohort_id' => $cohort?->id,
            'order_item_id' => $item->id, 'status' => EnrollmentStatus::Active,
        ]);
    }

    /**
     * @return Collection<int, Lesson>
     */
    private function orderedLessons(Program $program)
    {
        return Lesson::select('lessons.*')
            ->join('sections', 'sections.id', '=', 'lessons.section_id')
            ->where('sections.program_id', $program->id)
            ->orderBy('sections.position')->orderBy('lessons.position')
            ->get();
    }
}
