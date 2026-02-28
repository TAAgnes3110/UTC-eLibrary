<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

/** Dữ liệu mẫu: Khoa (UTC). */
class FacultySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'CNTT', 'name' => 'Công nghệ thông tin'],
            ['code' => 'CKD', 'name' => 'Cơ khí động lực'],
            ['code' => 'XD', 'name' => 'Xây dựng'],
            ['code' => 'KT', 'name' => 'Kinh tế vận tải'],
            ['code' => 'CKVT', 'name' => 'Cơ khí vận tải'],
        ];

        foreach ($items as $item) {
            Faculty::firstOrCreate(
                ['code' => $item['code']],
                array_merge($item, ['is_active' => true])
            );
        }
    }
}
