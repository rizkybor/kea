<?php

namespace Database\Seeders;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun demo, semua password: "password".
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['admin@kea.test', 'Admin KEA', Role::Admin, []],
            ['pm@kea.test', 'Program Manager KEA', Role::ProgramManager, []],
            ['mentor@kea.test', 'Raka Pratama', Role::Mentor, ['headline' => 'Senior Backend Engineer', 'company' => 'Mitra Teknologi']],
            ['mentor2@kea.test', 'Nadia Putri', Role::Mentor, ['headline' => 'Lead Frontend Engineer', 'company' => 'Studio Digital']],
            ['mentor3@kea.test', 'Dimas Wicaksono', Role::Mentor, ['headline' => 'Product Designer', 'company' => 'Startup Fintech']],
            ['learner@kea.test', 'Sari Wulandari', Role::Learner, ['phone' => '081234567890']],
            ['alumni@kea.test', 'Bima Santoso', Role::Learner, ['headline' => 'Junior Web Developer', 'phone' => '081298765432']],
        ];

        foreach ($accounts as [$email, $name, $role, $extra]) {
            User::factory()->create(['email' => $email, 'name' => $name, ...$extra])->assignRole($role->value);
        }
    }
}
