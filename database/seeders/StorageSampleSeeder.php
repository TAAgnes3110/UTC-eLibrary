<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\StorageCabinet;
use App\Models\StorageSlot;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class StorageSampleSeeder extends Seeder
{
    private const SLOT_CAPACITY = 30;

    public function run(): void
    {
        $classifications = Classification::query()->select(['id', 'name'])->get()->keyBy('id');
        if ($classifications->isEmpty()) return;

        $warehouseCodes = Warehouse::query()
            ->whereIn('code', ['KHO-GT', 'KHO-TK'])
            ->pluck('code', 'id');
        if ($warehouseCodes->isEmpty()) return;

        StorageSlot::query()->withTrashed()->forceDelete();
        StorageCabinet::query()->withTrashed()->forceDelete();

        $groups = Book::query()
            ->selectRaw('warehouse_id, classification_id, classification_detail_id, COALESCE(SUM(quantity),0) as total_qty')
            ->whereIn('warehouse_id', $warehouseCodes->keys())
            ->whereNotNull('classification_id')
            ->whereNotNull('classification_detail_id')
            ->groupBy('warehouse_id', 'classification_id', 'classification_detail_id')
            ->orderBy('warehouse_id')
            ->orderBy('classification_id')
            ->orderBy('classification_detail_id')
            ->get()
            ->groupBy(fn ($r) => (int) $r->warehouse_id.':'.(int) $r->classification_id);

        foreach ($groups as $groupKey => $rows) {
            [$warehouseId, $classificationId] = array_map('intval', explode(':', (string) $groupKey));
            $classification = $classifications->get($classificationId);
            if (! $classification) continue;

            $warehouseCode = (string) ($warehouseCodes->get($warehouseId) ?? 'KHO');
            $cabinetOrder = StorageCabinet::query()->where('warehouse_id', $warehouseId)->count() + 1;
            $cabinet = StorageCabinet::query()->create([
                'warehouse_id' => $warehouseId,
                'classification_id' => $classificationId,
                'code' => sprintf('TU-%s-%02d', str_replace('KHO-', '', $warehouseCode), $cabinetOrder),
                'name' => (string) $classification->name,
                'capacity_total' => 0,
                'current_quantity' => 0,
                'is_active' => true,
                'params' => ['seeded' => true],
            ]);

            $slotOrder = 1;
            foreach ($rows as $row) {
                $detail = ClassificationDetail::query()->find((int) $row->classification_detail_id, ['id', 'name']);
                if (! $detail) continue;

                $detailQty = max(0, (int) $row->total_qty);
                $slotCount = max(1, (int) ceil($detailQty / self::SLOT_CAPACITY));
                for ($i = 0; $i < $slotCount; $i++) {
                    $slotQty = max(0, min(self::SLOT_CAPACITY, $detailQty - ($i * self::SLOT_CAPACITY)));
                    StorageSlot::query()->create([
                        'storage_cabinet_id' => $cabinet->id,
                        'classification_detail_id' => (int) $detail->id,
                        'slot_code' => sprintf('NG-%02d', $slotOrder++),
                        'slot_name' => (string) $detail->name,
                        'capacity' => self::SLOT_CAPACITY,
                        'current_quantity' => $slotQty,
                        'is_active' => true,
                        'params' => ['seeded' => true],
                    ]);
                }
            }
        }

        StorageCabinet::query()->select('id')->chunkById(50, function ($cabinets) {
            foreach ($cabinets as $cabinetRow) {
                $cabinetId = (int) $cabinetRow->id;
                $stats = StorageSlot::query()
                    ->where('storage_cabinet_id', $cabinetId)
                    ->selectRaw('COALESCE(SUM(capacity),0) as capacity_total, COALESCE(SUM(current_quantity),0) as current_total')
                    ->first();

                StorageCabinet::query()
                    ->whereKey($cabinetId)
                    ->update([
                        'capacity_total' => (int) ($stats?->capacity_total ?? 0),
                        'current_quantity' => (int) ($stats?->current_total ?? 0),
                    ]);
            }
        });
    }
}
