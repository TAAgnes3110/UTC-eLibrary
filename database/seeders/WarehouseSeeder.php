<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $parents = [
            [
                'code' => 'KHO-GT',
                'name' => 'Kho Giáo trình',
                'params' => ['campus' => 'Hà Nội', 'floor' => 1, 'building' => 'A8', 'type' => 'textbook', 'note' => 'Tầng 1 - Nhà A8'],
            ],
            [
                'code' => 'KHO-TK',
                'name' => 'Kho Sách tham khảo & Luận án',
                'params' => ['campus' => 'Hà Nội', 'floor' => '5-6', 'building' => 'A8', 'type' => 'reference', 'note' => 'Tầng 5-6 - Nhà A8'],
            ],
            [
                'code' => 'KHO-SO',
                'name' => 'Kho Tài liệu số',
                'params' => ['campus' => 'Hà Nội', 'floor' => null, 'type' => 'digital'],
            ],
        ];

        $parentIds = [];
        foreach ($parents as $item) {
            $params = $item['params'] ?? [];
            unset($item['params']);

            $warehouse = Warehouse::updateOrCreate(
                ['code' => $item['code']],
                array_merge($item, [
                    'is_active' => true,
                    'params' => [],
                ])
            );
            DB::table('warehouses')
                ->where('id', $warehouse->id)
                ->update(['params' => json_encode($params, JSON_UNESCAPED_UNICODE)]);
            $parentIds[$item['code']] = $warehouse->id;
        }

        // Không tạo kho con; 3 kho chính đã đủ cho mô hình UTC hiện tại.
    }
}
