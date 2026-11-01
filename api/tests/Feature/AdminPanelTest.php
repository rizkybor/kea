<?php

namespace Tests\Feature;

use App\Modules\Identity\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCatalog;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use BuildsCatalog, RefreshDatabase;

    public function test_admin_can_open_every_resource_page(): void
    {
        $program = $this->program();
        $this->cohort($program);
        $this->actingAs($this->user(Role::Admin));

        $paths = ['', 'categories', 'programs', 'programs/create', "programs/{$program->slug}/edit", 'cohorts', 'cohorts/create', 'vouchers',
            'orders', 'enrollments', 'submissions', 'live-sessions', 'refund-requests', 'certificates', 'testimonials', 'users', 'users/create'];

        foreach ($paths as $path) {
            $this->get("/admin/{$path}")->assertOk();
        }
    }

    public function test_learner_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->user());

        $this->get('/admin')->assertForbidden();
    }
}
