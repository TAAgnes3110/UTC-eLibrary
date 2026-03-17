<?php

namespace Database\Seeders;

use App\Models\Classification;
use Illuminate\Database\Seeder;

class ClassificationSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => '000', 'name' => 'Khoa học máy tính, Thông tin & Tổng quát'],
            ['code' => '300', 'name' => 'Khoa học xã hội'],
            ['code' => '500', 'name' => 'Khoa học tự nhiên & Toán học'],
            ['code' => '600', 'name' => 'Công nghệ (Khoa học ứng dụng)'],
            ['code' => '700', 'name' => 'Nghệ thuật & Giải trí'],
            ['code' => '800', 'name' => 'Văn học'],
            ['code' => '900', 'name' => 'Lịch sử & Địa lý'],
        ];

        foreach ($items as $item) {
            Classification::firstOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'parent_id' => null,
                    'params' => null,
                ]
            );
        }
    }
}

