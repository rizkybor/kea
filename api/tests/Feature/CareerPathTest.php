<?php

namespace Tests\Feature;

use App\Modules\Career\Models\CareerAssessment;
use App\Modules\Catalog\Enums\ProgramType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsCatalog;
use Tests\TestCase;

class CareerPathTest extends TestCase
{
    use BuildsCatalog, RefreshDatabase;

    /**
     * @var array<string, mixed>
     */
    private array $answers = [
        'target_track' => 'unsure',
        'interests' => ['design'],
        'experience' => 'none',
        'status' => 'career_switcher',
        'availability' => 'weekend',
        'learning_style' => 'live',
        'budget' => 'mid',
    ];

    public function test_job_seeker_gets_guaranteed_bootcamp_with_cheaper_warm_up(): void
    {
        $bootcamp = $this->program(['title' => 'UI/UX Design', 'career_tracks' => ['ui_ux_designer'], 'has_job_guarantee' => true, 'promo_price' => 4_490_000, 'installment_from' => 375_000]);
        $this->cohort($bootcamp, ['schedule_type' => 'weekend']);
        $shortClass = $this->program(['title' => 'Figma', 'type' => ProgramType::ShortClass, 'career_tracks' => ['ui_ux_designer'], 'price' => 499_000, 'promo_price' => null]);
        $this->cohort($shortClass, ['schedule_type' => 'weekend']);
        $this->program(['title' => 'SEO', 'type' => ProgramType::VideoCourse, 'career_tracks' => ['digital_marketer']]);

        $data = $this->postJson('/api/v1/career/assessments', ['answers' => $this->answers])
            ->assertOk()
            ->assertJsonPath('data.target_label', 'UI/UX Designer')
            ->assertJsonPath('data.primary.program.slug', $bootcamp->slug)
            ->assertJsonPath('data.warm_up.program.slug', $shortClass->slug)
            ->assertJsonPath('data.saved', false)
            ->json('data');

        $this->assertContains('Ada garansi kerja & penyaluran ke mitra hiring', $data['primary']['reasons']);
        $this->assertEmpty($data['alternatives'], 'Program di luar minat tidak direkomendasikan');
        $this->assertDatabaseCount('career_assessments', 0);
    }

    public function test_full_cohorts_are_never_recommended(): void
    {
        $bootcamp = $this->program(['career_tracks' => ['ui_ux_designer']]);
        $this->cohort($bootcamp, ['quota' => 5, 'seats_taken' => 5]);

        $this->postJson('/api/v1/career/assessments', ['answers' => $this->answers])
            ->assertOk()
            ->assertJsonPath('data.primary', null);
    }

    public function test_logged_in_result_is_saved_and_retrievable(): void
    {
        $video = $this->program(['type' => ProgramType::VideoCourse, 'career_tracks' => ['software_engineer'], 'price' => 249_000, 'promo_price' => null]);
        $user = $this->user();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/career/assessments', ['answers' => [...$this->answers, 'target_track' => 'software_engineer', 'learning_style' => 'self_paced', 'budget' => 'low']])
            ->assertOk()
            ->assertJsonPath('data.saved', true)
            ->assertJsonPath('data.primary.program.slug', $video->slug);

        $this->assertSame('software_engineer', CareerAssessment::where('user_id', $user->id)->value('target_track'));
        $this->getJson('/api/v1/me/career-assessment')->assertJsonPath('data.primary.program.slug', $video->slug);
    }

    public function test_answers_are_validated(): void
    {
        $this->postJson('/api/v1/career/assessments', ['answers' => [...$this->answers, 'interests' => [], 'budget' => 'gratis']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.interests', 'answers.budget']);
    }
}
