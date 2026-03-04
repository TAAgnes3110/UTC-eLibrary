<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeder chính: nền tảng → User → Thẻ → Sách → Phiếu mượn.
 * Chạy: php artisan db:seed (hoặc migrate:fresh --seed).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            FacultySeeder::class,
            DepartmentSeeder::class,
            CategorySeeder::class,
            LibrarySettingsSeeder::class,
            DefaultUsersSeeder::class,
            LibraryCardsAndMembersSeeder::class,
            BookSeeder::class,
            LoanSeeder::class,
        ]);
    }
}
