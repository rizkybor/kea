<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data demo KEA. Harga, tanggal, dan nama di sini adalah placeholder untuk development.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            CatalogSeeder::class,
            CommerceSeeder::class,
            LearningSeeder::class,
            CareerSeeder::class,
        ]);
    }
}
