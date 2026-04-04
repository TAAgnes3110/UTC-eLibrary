<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Seeder;

/**
 * Các khoa tại Đại học Giao thông Vận tải (UTC).
 * Bảy khoa nền theo mô tả nghiệp vụ + các đơn vị bổ trợ trong hệ thống.
 */
class FacultySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'CT_XDCTGT', 'name' => 'Khoa Công trình', 'params' => ['description' => 'Chuyên về xây dựng cầu đường, hạ tầng giao thông.']],
            ['code' => 'CK', 'name' => 'Khoa Cơ khí', 'params' => ['description' => 'Đào tạo kỹ thuật cơ khí, máy xây dựng, ô tô.']],
            ['code' => 'DDT', 'name' => 'Khoa Điện – Điện tử', 'params' => ['description' => 'Bao gồm kỹ thuật điện tử, viễn thông, tự động hóa.']],
            ['code' => 'CNTT', 'name' => 'Khoa Công nghệ thông tin', 'params' => ['description' => 'Đào tạo khoa học máy tính, kỹ thuật phần mềm, hệ thống thông tin.']],
            ['code' => 'VT', 'name' => 'Khoa Vận tải – Kinh tế', 'params' => ['description' => 'Quản lý vận tải, logistics, kinh tế xây dựng.']],
            ['code' => 'KHCB', 'name' => 'Khoa Khoa học cơ bản', 'params' => ['description' => 'Giảng dạy toán, lý, hóa nền tảng.']],
            ['code' => 'LLCT', 'name' => 'Khoa Lý luận chính trị', 'params' => ['description' => 'Giảng dạy các môn Mác-Lênin, tư tưởng Hồ Chí Minh.']],
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
            Faculty::updateOrCreate(
                ['code' => $item['code']],
                array_merge($item, ['is_active' => true, 'params' => $params])
            );
        }
    }
}
