<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run seeders in correct order due to foreign key dependencies
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            TeamSeeder::class,
        ]);
    }
}
