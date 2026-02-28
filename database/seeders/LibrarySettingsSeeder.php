<?php

namespace Database\Seeders;

use App\Models\LibrarySetting;
use Illuminate\Database\Seeder;

/** Gọi seed quy định mượn/trả/phạt (library_settings). */
class LibrarySettingsSeeder extends Seeder
{
    public function run(): void
    {
        LibrarySetting::seedDefaults();
    }
}
