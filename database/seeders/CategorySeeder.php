<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/** Dữ liệu mẫu: Danh mục phân loại sách. */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'TK', 'name' => 'Công nghệ thông tin', 'order' => 1],
            ['code' => 'CK', 'name' => 'Cơ khí', 'order' => 2],
            ['code' => 'XD', 'name' => 'Xây dựng', 'order' => 3],
            ['code' => 'KT', 'name' => 'Kinh tế', 'order' => 4],
            ['code' => 'VH', 'name' => 'Văn học', 'order' => 5],
            ['code' => 'LS', 'name' => 'Lịch sử - Địa lý', 'order' => 6],
            ['code' => 'NN', 'name' => 'Ngoại ngữ', 'order' => 7],
            ['code' => 'KH', 'name' => 'Khoa học khác', 'order' => 8],
        ];

        foreach ($items as $item) {
            Category::firstOrCreate(
                ['code' => $item['code']],
                array_merge($item, ['is_active' => true])
            );
        }
    }
}
