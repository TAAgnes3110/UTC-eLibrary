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
            CmsAndContentSeeder::class,
            BookSampleSeeder::class,
        ]);
    }
}
