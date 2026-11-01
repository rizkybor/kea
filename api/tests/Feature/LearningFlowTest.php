<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Lesson;
use App\Modules\Identity\Enums\Role;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Models\Enrollment;
use App\Modules\Learning\Models\LiveSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsCatalog;
use Tests\TestCase;

class LearningFlowTest extends TestCase
{
    use BuildsCatalog, RefreshDatabase;

    public function test_completing_lessons_and_passing_assignment_issues_a_certificate(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program, ['starts_at' => now()->subWeek()]);
        $learner = $this->user();
        $mentor = $this->user(Role::Mentor);
        $cohort->mentors()->attach($mentor);
        $enrollment = Enrollment::create(['user_id' => $learner->id, 'program_id' => $program->id, 'cohort_id' => $cohort->id]);
        [$video, $assignment] = Lesson::orderBy('position')->get();

        Sanctum::actingAs($learner);
        $this->putJson("/api/v1/enrollments/{$enrollment->id}/lessons/{$video->id}/progress", ['completed' => true])
            ->assertOk()
            ->assertJsonPath('data.progress_pct', 50);
        $this->postJson("/api/v1/enrollments/{$enrollment->id}/lessons/{$assignment->id}/submissions", ['link_url' => 'https://github.com/sari/final'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted');
        $this->putJson("/api/v1/enrollments/{$enrollment->id}/lessons/{$assignment->id}/progress", ['completed' => true])
            ->assertJsonPath('data.progress_pct', 100)
            ->assertJsonPath('data.status', 'active');

        Sanctum::actingAs($mentor);
        $submissionId = $this->getJson('/api/v1/mentor/submissions')->assertOk()->json('data.0.id');
        $this->putJson("/api/v1/mentor/submissions/{$submissionId}/grade", ['score' => 85, 'feedback' => 'Bagus', 'status' => 'reviewed'])
            ->assertOk();

        $this->assertSame(EnrollmentStatus::Completed, $enrollment->fresh()->status);
        $code = $enrollment->fresh()->certificate->code;
        $this->assertSame(sprintf('KEA-FSWD-%s-%06d', now()->format('Y'), $enrollment->id), $code);

        $this->getJson("/api/v1/certificates/verify/{$code}")
            ->assertOk()
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.final_score', 85);
    }

    public function test_mentor_cannot_grade_submissions_outside_their_cohort(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program);
        $learner = $this->user();
        $enrollment = Enrollment::create(['user_id' => $learner->id, 'program_id' => $program->id, 'cohort_id' => $cohort->id]);
        $assignment = Lesson::whereNotNull('assignment_title')->firstOrFail();

        Sanctum::actingAs($learner);
        $submissionId = $this->postJson("/api/v1/enrollments/{$enrollment->id}/lessons/{$assignment->id}/submissions", ['link_url' => 'https://github.com/x'])
            ->json('data.id');

        Sanctum::actingAs($this->user(Role::Mentor));
        $this->putJson("/api/v1/mentor/submissions/{$submissionId}/grade", ['score' => 90, 'status' => 'reviewed'])->assertNotFound();
    }

    public function test_live_session_link_opens_fifteen_minutes_before_start_and_records_attendance(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program);
        $learner = $this->user();
        $enrollment = Enrollment::create(['user_id' => $learner->id, 'program_id' => $program->id, 'cohort_id' => $cohort->id]);
        $session = LiveSession::create([
            'cohort_id' => $cohort->id, 'title' => 'Sesi 1', 'join_url' => 'https://zoom.us/j/1',
            'starts_at' => now()->addMinutes(30), 'ends_at' => now()->addMinutes(150),
        ]);
        Sanctum::actingAs($learner);

        $this->getJson("/api/v1/enrollments/{$enrollment->id}/live-sessions/{$session->id}/join")
            ->assertStatus(423)
            ->assertJsonPath('code', 'session_not_open');

        $this->travel(16)->minutes();

        $this->getJson("/api/v1/enrollments/{$enrollment->id}/live-sessions/{$session->id}/join")
            ->assertOk()
            ->assertJsonPath('data.join_url', 'https://zoom.us/j/1');
        $this->assertDatabaseHas('attendances', ['enrollment_id' => $enrollment->id, 'live_session_id' => $session->id]);
    }

    public function test_assignment_deadline_is_end_of_day_in_jakarta_time(): void
    {
        $program = $this->program();
        $cohort = $this->cohort($program, ['starts_at' => '2026-11-12 12:00:00']); // 19.00 WIB
        $learner = $this->user();
        $enrollment = Enrollment::create(['user_id' => $learner->id, 'program_id' => $program->id, 'cohort_id' => $cohort->id]);
        $assignment = Lesson::whereNotNull('assignment_title')->firstOrFail();
        Sanctum::actingAs($learner);

        $this->getJson("/api/v1/enrollments/{$enrollment->id}/lessons/{$assignment->id}")
            ->assertOk()
            ->assertJsonPath('data.assignment.due_at', '2026-11-19T23:59:00+07:00');
    }

    public function test_learner_cannot_open_another_learners_classroom(): void
    {
        $program = $this->program();
        $enrollment = Enrollment::create(['user_id' => $this->user()->id, 'program_id' => $program->id]);
        Sanctum::actingAs($this->user());

        $this->getJson("/api/v1/enrollments/{$enrollment->id}/curriculum")->assertForbidden();
    }
}
