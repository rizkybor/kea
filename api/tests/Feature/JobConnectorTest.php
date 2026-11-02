<?php

namespace Tests\Feature;

use App\Modules\Career\Enums\ApplicationStatus;
use App\Modules\Career\Enums\EmploymentType;
use App\Modules\Career\Enums\JobStatus;
use App\Modules\Career\Enums\WorkMode;
use App\Modules\Career\Models\Company;
use App\Modules\Career\Models\JobApplication;
use App\Modules\Career\Models\JobPosting;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Learning\Enums\EnrollmentStatus;
use App\Modules\Learning\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsCatalog;
use Tests\TestCase;

class JobConnectorTest extends TestCase
{
    use BuildsCatalog, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_alumni_with_cv_can_apply_and_cv_is_snapshotted(): void
    {
        $job = $this->job();
        $alumni = $this->alumni();
        Sanctum::actingAs($alumni);

        $id = $this->postJson("/api/v1/jobs/{$job->slug}/apply", ['cover_letter' => 'Halo'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'applied')
            ->assertJsonPath('data.events.0.status', 'applied')
            ->json('data.id');

        Storage::disk('local')->assertExists("applications/{$id}/cv.pdf");

        $this->postJson("/api/v1/jobs/{$job->slug}/apply")->assertStatus(409)->assertJsonPath('code', 'already_applied');
    }

    public function test_applying_requires_cv_and_alumni_status_for_alumni_only_jobs(): void
    {
        $job = $this->job();
        $learner = $this->user();
        Sanctum::actingAs($learner);

        $this->postJson("/api/v1/jobs/{$job->slug}/apply")->assertStatus(422)->assertJsonPath('code', 'cv_required');

        Storage::disk('local')->put('cv/x.pdf', '%PDF');
        $learner->update(['cv_path' => 'cv/x.pdf']);

        $this->postJson("/api/v1/jobs/{$job->slug}/apply")->assertForbidden()->assertJsonPath('code', 'alumni_only');

        $job->update(['alumni_only' => false]);
        $this->postJson("/api/v1/jobs/{$job->slug}/apply")->assertCreated();
    }

    public function test_closed_jobs_are_hidden_and_cannot_be_applied(): void
    {
        $job = $this->job(['closes_at' => now()->subDay()]);
        Sanctum::actingAs($this->alumni());

        $this->getJson('/api/v1/jobs')->assertJsonCount(0, 'data');
        $this->getJson("/api/v1/jobs/{$job->slug}")->assertNotFound();
        $this->postJson("/api/v1/jobs/{$job->slug}/apply")->assertStatus(409);
    }

    public function test_partner_moves_applicant_through_pipeline_and_hire_is_recorded(): void
    {
        $job = $this->job();
        $partner = $this->partner($job->company);
        $application = $this->applicationFor($job, $this->alumni());
        Sanctum::actingAs($partner);

        $this->getJson('/api/v1/me')
            ->assertJsonPath('data.company', 'Teks perusahaan di profil')
            ->assertJsonPath('data.partner_company.id', $job->company_id);

        $this->getJson("/api/v1/partner/jobs/{$job->id}/applicants")
            ->assertOk()
            ->assertJsonPath('data.0.talent.programs.0.title', 'Fullstack Web Development');

        $this->putJson("/api/v1/partner/applications/{$application->id}", ['status' => 'interview', 'note' => 'Kamis 10.00'])
            ->assertOk()
            ->assertJsonPath('data.status', 'interview');
        $this->putJson("/api/v1/partner/applications/{$application->id}", ['status' => 'hired'])->assertOk();

        $application->refresh();
        $this->assertSame(ApplicationStatus::Hired, $application->status);
        $this->assertNotNull($application->hired_at);
        $this->assertSame(['applied', 'interview', 'hired'], $application->events()->pluck('status')->map->value->all());

        $this->putJson("/api/v1/partner/applications/{$application->id}", ['status' => 'rejected'])->assertStatus(409);
    }

    public function test_partner_can_post_a_job_that_appears_on_the_public_board(): void
    {
        $this->program(); // membuat kategori "teknologi"
        $company = $this->company();
        Sanctum::actingAs($this->partner($company));

        $this->postJson('/api/v1/partner/jobs', [
            'title' => 'QA Engineer',
            'category_slug' => 'teknologi',
            'employment_type' => 'full_time',
            'work_mode' => 'remote',
            'salary_min' => 6_000_000,
            'salary_max' => 5_000_000,
            'description' => 'Deskripsi',
            'status' => 'open',
        ])->assertUnprocessable()->assertJsonValidationErrors('salary_max');

        $slug = $this->postJson('/api/v1/partner/jobs', [
            'title' => 'QA Engineer',
            'category_slug' => 'teknologi',
            'employment_type' => 'full_time',
            'work_mode' => 'remote',
            'show_salary' => false,
            'salary_min' => 6_000_000,
            'description' => 'Deskripsi',
            'skills' => ['Cypress'],
            'status' => 'open',
        ])->assertCreated()
            ->assertJsonPath('data.category.slug', 'teknologi')
            ->assertJsonPath('data.salary_min', 6_000_000)
            ->json('data.slug');

        $this->getJson("/api/v1/jobs/{$slug}")
            ->assertOk()
            ->assertJsonPath('data.company.name', $company->name)
            ->assertJsonPath('data.salary_min', null);
    }

    public function test_partner_cannot_touch_another_companys_jobs_or_applicants(): void
    {
        $job = $this->job();
        $application = $this->applicationFor($job, $this->alumni());
        Sanctum::actingAs($this->partner($this->company('Lain')));

        $this->getJson("/api/v1/partner/jobs/{$job->id}/applicants")->assertNotFound();
        $this->putJson("/api/v1/partner/applications/{$application->id}", ['status' => 'hired'])->assertNotFound();
        $this->getJson("/api/v1/partner/applications/{$application->id}/cv")->assertNotFound();
    }

    public function test_learner_cannot_use_partner_endpoints(): void
    {
        Sanctum::actingAs($this->alumni());

        $this->getJson('/api/v1/partner/talents')->assertForbidden();
    }

    public function test_talent_pool_only_lists_consenting_alumni_and_filters_by_skill(): void
    {
        $visible = $this->alumni(['skills' => ['Laravel', 'Vue'], 'talent_consent_at' => now()]);
        $this->alumni(['skills' => ['Laravel'], 'talent_consent_at' => null]);
        $this->user()->update(['skills' => ['Laravel'], 'talent_consent_at' => now()]); // belum lulus
        Sanctum::actingAs($this->partner($this->company('Mitra')));

        $this->getJson('/api/v1/partner/talents?skill=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visible->id)
            ->assertJsonMissingPath('data.0.email');

        $this->getJson('/api/v1/partner/talents?skill=figma')->assertJsonCount(0, 'data');
    }

    public function test_learner_can_withdraw_until_application_is_final(): void
    {
        $alumni = $this->alumni();
        $application = $this->applicationFor($this->job(), $alumni);
        Sanctum::actingAs($alumni);

        $this->postJson("/api/v1/me/applications/{$application->id}/withdraw")->assertOk()->assertJsonPath('data.status', 'withdrawn');
        $this->postJson("/api/v1/me/applications/{$application->id}/withdraw")->assertStatus(409);
    }

    public function test_profile_consent_is_timestamped_and_revocable(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/me', ['name' => $user->name, 'talent_consent' => true, 'skills' => [' Laravel ', 'Laravel', 'Vue']])
            ->assertOk()
            ->assertJsonPath('data.talent_consent', true)
            ->assertJsonPath('data.skills', ['Laravel', 'Vue']);
        $this->assertNotNull($user->fresh()->talent_consent_at);

        $this->putJson('/api/v1/me', ['name' => $user->name, 'talent_consent' => false])->assertJsonPath('data.talent_consent', false);
        $this->assertNull($user->fresh()->talent_consent_at);
    }

    private function company(string $name = 'Nusantara Fintech'): Company
    {
        return Company::create(['name' => $name, 'slug' => Str::slug($name).'-'.uniqid()]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function job(array $attributes = []): JobPosting
    {
        return $this->company()->jobPostings()->create([
            'slug' => 'backend-'.uniqid(),
            'title' => 'Junior Backend Engineer',
            'employment_type' => EmploymentType::FullTime,
            'work_mode' => WorkMode::Remote,
            'description' => 'Deskripsi',
            'alumni_only' => true,
            'status' => JobStatus::Open,
            'published_at' => now()->subDay(),
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function alumni(array $attributes = []): User
    {
        $user = $this->user();
        $program = $this->program();
        Enrollment::create(['user_id' => $user->id, 'program_id' => $program->id, 'status' => EnrollmentStatus::Completed, 'completed_at' => now()]);
        Storage::disk('local')->put("cv/{$user->id}.pdf", '%PDF');
        $user->update(['cv_path' => "cv/{$user->id}.pdf", ...$attributes]);

        return $user;
    }

    private function partner(Company $company): User
    {
        $partner = $this->user(Role::Partner);
        $partner->update(['company_id' => $company->id, 'company' => 'Teks perusahaan di profil']);

        // Dimuat ulang dari DB supaya semua kolom (termasuk kolom teks `company`) terisi seperti saat login.
        return $partner->fresh();
    }

    private function applicationFor(JobPosting $job, User $user): JobApplication
    {
        $application = JobApplication::create(['job_posting_id' => $job->id, 'user_id' => $user->id, 'cv_path' => $user->cv_path]);
        $application->events()->create(['status' => ApplicationStatus::Applied, 'actor_id' => $user->id, 'created_at' => now()->subMinute()]);

        return $application;
    }
}
