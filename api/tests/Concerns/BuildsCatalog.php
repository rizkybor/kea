<?php

namespace Tests\Concerns;

use App\Modules\Catalog\Enums\LessonType;
use App\Modules\Catalog\Enums\ProgramType;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Cohort;
use App\Modules\Catalog\Models\Program;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Spatie\Permission\Models\Role as RoleModel;

trait BuildsCatalog
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function program(array $attributes = []): Program
    {
        $category = Category::firstOrCreate(['slug' => 'teknologi'], ['name' => 'Teknologi']);

        $program = Program::create([
            'category_id' => $category->id,
            'type' => ProgramType::Bootcamp,
            'code' => 'FSWD',
            'slug' => 'fullstack-'.uniqid(),
            'title' => 'Fullstack Web Development',
            'summary' => 'Ringkasan',
            'price' => 5_000_000,
            'promo_price' => 4_000_000,
            'is_published' => true,
            ...$attributes,
        ]);

        $section = $program->sections()->create(['title' => 'Modul 1', 'position' => 1]);
        $section->lessons()->create(['title' => 'Video 1', 'type' => LessonType::Video, 'position' => 1]);
        $section->lessons()->create([
            'title' => 'Tugas 1', 'type' => LessonType::Assignment, 'position' => 2,
            'assignment_title' => 'Tugas 1', 'assignment_instructions' => 'Kerjakan', 'assignment_due_day' => 7, 'is_final_project' => true,
        ]);

        return $program;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function cohort(Program $program, array $attributes = []): Cohort
    {
        return $program->cohorts()->create([
            'name' => 'Batch 1',
            'starts_at' => now()->addWeek(),
            'quota' => 10,
            'seats_taken' => 0,
            ...$attributes,
        ]);
    }

    protected function user(Role $role = Role::Learner, bool $verified = true): User
    {
        RoleModel::findOrCreate($role->value, 'web');

        $factory = User::factory();

        return ($verified ? $factory : $factory->unverified())->create()->assignRole($role->value);
    }
}
