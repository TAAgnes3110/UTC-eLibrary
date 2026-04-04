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
                'code' => 'KHO-GT',
                'name' => 'Kho Giáo trình (Tầng 1 - Nhà A8)',
                'params' => ['campus' => 'Hà Nội', 'floor' => 1, 'type' => 'textbook'],
            ],
            [
                'code' => 'KHO-TK',
                'name' => 'Kho Sách tham khảo & Luận án (Tầng 5-6 - Nhà A8)',
                'params' => ['campus' => 'Hà Nội', 'floor' => '5-6', 'type' => 'reference'],
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

            $warehouse = Warehouse::firstOrCreate(
                ['code' => $item['code']],
                array_merge($item, [
                    'is_active' => true,
                    'params' => $params,
                ])
            );
            $parentIds[$item['code']] = $warehouse->id;
        }

        // Không tạo kho con; 3 kho chính đã đủ cho mô hình UTC hiện tại.
    }
}
