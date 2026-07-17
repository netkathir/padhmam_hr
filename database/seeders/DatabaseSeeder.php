<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * WithoutModelEvents is intentionally not used here: branch assignment
     * relies on the creating/updating model events registered by the
     * BelongsToBranch trait.
     */
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            BranchSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);
    }
}
