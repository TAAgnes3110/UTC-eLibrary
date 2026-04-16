<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            FacultySeeder::class,
            DepartmentSeeder::class,
            WarehouseSeeder::class,
            ClassificationSeeder::class,
            ClassificationDetailSeeder::class,
            DefaultUsersSeeder::class,
            LoanPoliciesSeeder::class,
            CmsAndContentSeeder::class,
            PeriodSeeder::class,
            StaffReviewDemoSeeder::class,
        ]);

        if (filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOL)) {
            $this->call([
                BookSampleSeeder::class,
                LibraryCardSampleSeeder::class,
                ManagementPaginationSeeder::class,
            ]);
        }
    }
}
