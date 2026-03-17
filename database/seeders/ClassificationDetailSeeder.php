<?php

namespace Database\Seeders;

use App\Models\Classification;
use App\Models\ClassificationDetail;
use Illuminate\Database\Seeder;

class ClassificationDetailSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // 000 – Khoa học máy tính, Thông tin & Tổng quát
            ['code' => '004',    'name' => 'Khoa học máy tính', 'classification_code' => '000'],
            ['code' => '005',    'name' => 'Lập trình, phần mềm', 'classification_code' => '000'],
            ['code' => '005.13', 'name' => 'Ngôn ngữ lập trình (Java, PHP, Laravel...)', 'classification_code' => '000'],

            // 300 – Khoa học xã hội
            ['code' => '330',   'name' => 'Kinh tế học', 'classification_code' => '300'],
            ['code' => '388.4', 'name' => 'Kinh tế vận tải, Logistics', 'classification_code' => '300'],
            ['code' => '343.7', 'name' => 'Luật giao thông', 'classification_code' => '300'],
            ['code' => '658',   'name' => 'Quản trị kinh doanh', 'classification_code' => '300'],

            // 500 – Khoa học tự nhiên & Toán học
            ['code' => '510',   'name' => 'Toán học', 'classification_code' => '500'],
            ['code' => '530',   'name' => 'Vật lý', 'classification_code' => '500'],
            ['code' => '540',   'name' => 'Hóa học', 'classification_code' => '500'],
            ['code' => '519.5', 'name' => 'Xác suất thống kê', 'classification_code' => '500'],

            // 600 – Công nghệ (Khoa học ứng dụng)
            ['code' => '620',   'name' => 'Kỹ thuật Công trình, Cơ khí, Điện - Điện tử', 'classification_code' => '600'],
            ['code' => '624',   'name' => 'Kỹ thuật Xây dựng (Cầu đường, Công trình dân dụng)', 'classification_code' => '600'],
            ['code' => '625',   'name' => 'Kỹ thuật Đường bộ và Đường sắt', 'classification_code' => '600'],
            ['code' => '629',   'name' => 'Kỹ thuật Ô tô, Máy xây dựng', 'classification_code' => '600'],

            // 700 – Nghệ thuật & Giải trí
            ['code' => '720',   'name' => 'Kiến trúc, quy hoạch đô thị', 'classification_code' => '700'],

            // 800 – Văn học
            ['code' => '895.922', 'name' => 'Văn học Việt Nam', 'classification_code' => '800'],
            ['code' => '820',     'name' => 'Văn học Anh & một số văn học thế giới', 'classification_code' => '800'],

            // 900 – Lịch sử & Địa lý
            ['code' => '959.704', 'name' => 'Lịch sử Đảng, lịch sử Việt Nam hiện đại', 'classification_code' => '900'],
            ['code' => '910',     'name' => 'Địa lý kinh tế, du lịch', 'classification_code' => '900'],
        ];

        $classificationCodes = collect($items)->pluck('classification_code')->unique()->all();
        $classificationMap = Classification::whereIn('code', $classificationCodes)->get()->keyBy('code');

        foreach ($items as $item) {
            $classification = $classificationMap[$item['classification_code']] ?? null;
            if (!$classification) {
                continue;
            }

            ClassificationDetail::firstOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'classification_id' => $classification->id,
                    'parent_id' => null,
                    'params' => null,
                ]
            );
        }
    }
}

