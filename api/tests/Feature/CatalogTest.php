<?php

namespace Tests\Feature;

use App\Modules\Catalog\Enums\ProgramType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCatalog;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use BuildsCatalog, RefreshDatabase;

    public function test_catalog_filters_by_type_and_hides_unpublished_programs(): void
    {
        $bootcamp = $this->program();
        $this->cohort($bootcamp);
        $this->program(['type' => ProgramType::VideoCourse, 'title' => 'SEO Fundamental']);
        $this->program(['is_published' => false, 'title' => 'Draft']);

        $this->getJson('/api/v1/programs?type=bootcamp')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $bootcamp->slug)
            ->assertJsonPath('data.0.next_cohort.seats_left', 10);

        $this->getJson('/api/v1/programs?q=seo')->assertJsonCount(1, 'data');
    }

    public function test_program_detail_includes_syllabus_and_cohorts(): void
    {
        $program = $this->program();
        $this->cohort($program, ['quota' => 5, 'seats_taken' => 5]);

        $this->getJson("/api/v1/programs/{$program->slug}")
            ->assertOk()
            ->assertJsonPath('data.sections.0.lessons.1.title', 'Tugas 1')
            ->assertJsonPath('data.cohorts.0.is_available', false);
    }

    public function test_cached_program_detail_keeps_the_same_shape(): void
    {
        config(['cache.default' => 'file']);
        $program = $this->program();
        $this->cohort($program);

        $first = $this->getJson("/api/v1/programs/{$program->slug}")->assertOk()->json('data');
        $cached = $this->getJson("/api/v1/programs/{$program->slug}")->assertOk()->json('data');

        $this->assertSame($first, $cached);
        $this->assertIsList($cached['cohorts']);
    }

    public function test_unpublished_program_detail_is_not_found(): void
    {
        $program = $this->program(['is_published' => false]);

        $this->getJson("/api/v1/programs/{$program->slug}")->assertNotFound();
    }
}
