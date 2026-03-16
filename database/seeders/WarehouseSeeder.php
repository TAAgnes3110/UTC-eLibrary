<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $parents = [
            [
                'code' => 'TV-MUON',
                'name' => 'Kho mượn (Giáo trình & Sách tham khảo)',
                'params' => ['campus' => 'Hà Nội', 'floor' => 1, 'mode' => 'closed_stack'],
            ],
            [
                'code' => 'TV-DOC-TV',
                'name' => 'Phòng đọc Tiếng Việt',
                'params' => ['campus' => 'Hà Nội', 'floor' => 5, 'mode' => 'reading_room'],
            ],
            [
                'code' => 'TV-DOC-NN',
                'name' => 'Phòng đọc Ngoại văn & Tài liệu đặc biệt',
                'params' => ['campus' => 'Hà Nội', 'floor' => 6, 'mode' => 'special_collection'],
            ],
            [
                'code' => 'TV-SO',
                'name' => 'Tài nguyên số',
                'params' => ['campus' => 'Hà Nội', 'floor' => null, 'mode' => 'digital'],
            ],
        ];

        $parentIds = [];
        foreach ($parents as $item) {
            $params = $item['params'] ?? [];
            unset($item['params']);

            $warehouse = Warehouse::firstOrCreate(
                ['code' => $item['code']],
                array_merge($item, [
                    'is_active' => true,
                    'params' => $params,
                ])
            );
            $parentIds[$item['code']] = $warehouse->id;
        }

        $children = [
            [
                'code' => '07',
                'name' => 'Sách nghiệp vụ',
                'parent_code' => 'TV-MUON',
                'params' => ['type' => 'professional'],
            ],
            [
                'code' => '075',
                'name' => 'Sách giáo khoa / Giáo trình',
                'parent_code' => 'TV-MUON',
                'params' => ['type' => 'textbook'],
            ],
            [
                'code' => '076',
                'name' => 'Sách bài tập',
                'parent_code' => 'TV-MUON',
                'params' => ['type' => 'exercise'],
            ],
            [
                'code' => '083',
                'name' => 'Sách tham khảo',
                'parent_code' => 'TV-MUON',
                'params' => ['type' => 'reference'],
            ],
            [
                'code' => 'BAO',
                'name' => 'Báo, tạp chí tiếng Việt',
                'parent_code' => 'TV-DOC-TV',
                'params' => ['type' => 'newspaper_vi'],
            ],
            [
                'code' => 'TC',
                'name' => 'Tạp chí khoa học, chuyên ngành',
                'parent_code' => 'TV-DOC-NN',
                'params' => ['type' => 'journal'],
            ],
            [
                'code' => 'TA',
                'name' => 'Tranh ảnh, bản đồ',
                'parent_code' => 'TV-DOC-TV',
                'params' => ['type' => 'visual'],
            ],
            [
                'code' => 'BD',
                'name' => 'Băng đĩa, tài liệu số ngoại tuyến',
                'parent_code' => 'TV-SO',
                'params' => ['type' => 'media_offline'],
            ],
        ];

        foreach ($children as $item) {
            $params = $item['params'] ?? [];
            $parentCode = $item['parent_code'] ?? null;
            unset($item['params'], $item['parent_code']);

            Warehouse::firstOrCreate(
                ['code' => $item['code']],
                array_merge($item, [
                    'parent_id' => $parentCode && isset($parentIds[$parentCode]) ? $parentIds[$parentCode] : null,
                    'is_active' => true,
                    'params' => $params,
                ])
            );
        }
    }
}


