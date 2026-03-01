<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

/**
 * Các Khoa – Bộ môn tại UTC (Hà Nội).
 * 12 khoa chính + 1 bộ môn trực thuộc trường.
 */
class FacultySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'CT_XDCTGT', 'name' => 'Khoa Công trình – Xây dựng Công trình Giao thông', 'params' => ['description' => 'Xây dựng Công trình Giao thông.']],
            ['code' => 'CK', 'name' => 'Khoa Cơ khí', 'params' => ['description' => 'Kỹ thuật cơ khí, ô tô, máy móc.']],
            ['code' => 'VT', 'name' => 'Khoa Vận tải – Kinh tế', 'params' => ['description' => 'Quản lý vận tải, kinh tế vận tải, Logistics.']],
            ['code' => 'DDT', 'name' => 'Khoa Điện – Điện tử', 'params' => ['description' => 'Điện, điện tử, điều khiển tự động.']],
            ['code' => 'CNTT', 'name' => 'Khoa Công nghệ thông tin', 'params' => ['description' => 'CNTT, phần mềm, mạng.']],
            ['code' => 'KHCB', 'name' => 'Khoa Khoa học cơ bản', 'params' => ['description' => 'Toán, Lý, Hóa, Anh văn.']],
            ['code' => 'LLCT', 'name' => 'Khoa Lý luận chính trị', 'params' => ['description' => 'Giáo dục chính trị.']],
            ['code' => 'GDQP', 'name' => 'Khoa Giáo dục quốc phòng', 'params' => ['description' => 'GDQP & AN.']],
            ['code' => 'MT_ATGT', 'name' => 'Khoa Môi trường và An toàn giao thông', 'params' => ['description' => 'ATGT & môi trường.']],
            ['code' => 'KTXD', 'name' => 'Khoa Kỹ thuật xây dựng', 'params' => ['description' => 'Xây dựng kỹ thuật (dân dụng & công nghiệp).']],
            ['code' => 'QLXD', 'name' => 'Khoa Quản lý xây dựng', 'params' => ['description' => 'Quản lý dự án & xây dựng.']],
            ['code' => 'INED', 'name' => 'Khoa Đào tạo quốc tế (INED)', 'params' => ['description' => 'Chương trình chất lượng cao, liên kết quốc tế.']],
            ['code' => 'BM_GDTC', 'name' => 'Bộ môn Giáo dục thể chất', 'params' => ['description' => 'Trực thuộc trường, giáo dục thể lực cho sinh viên.']],
        ];

        foreach ($items as $item) {
            $params = $item['params'] ?? [];
            unset($item['params']);
            Faculty::firstOrCreate(
                ['code' => $item['code']],
                array_merge($item, ['is_active' => true, 'params' => $params])
            );
        }
    }
}
