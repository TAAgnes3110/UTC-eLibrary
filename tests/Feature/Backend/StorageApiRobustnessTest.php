<?php

namespace Tests\Feature\Backend;

use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\StorageCabinet;
use App\Models\StorageSlot;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageApiRobustnessTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_storage_cabinets_per_page_is_capped_to_100(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $warehouse = $this->createWarehouse();
        $classification = $this->createClassification();

        StorageCabinet::query()->create([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-A1-01',
            'name' => 'Tu 1',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/storage-cabinets?per_page=9999', $this->apiTokenHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.meta.per_page', 100);
    }

    public function test_storage_cabinets_without_slots_remain_lightweight(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $warehouse = $this->createWarehouse();
        $classification = $this->createClassification();
        $detail = $this->createClassificationDetail($classification);
        $cabinet = StorageCabinet::query()->create([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-A1-01',
            'name' => 'Tu 1',
            'is_active' => true,
        ]);

        StorageSlot::query()->create([
            'storage_cabinet_id' => $cabinet->id,
            'classification_detail_id' => $detail->id,
            'slot_name' => 'Ngan 1',
            'capacity' => 30,
            'current_quantity' => 5,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/storage-cabinets?with_slots=false', $this->apiTokenHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $first = $response->json('data.data.0');
        $this->assertArrayNotHasKey('slots', $first);
    }

    public function test_storage_slots_support_sort_by_name_ascending(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $warehouse = $this->createWarehouse();
        $classification = $this->createClassification();
        $detail = $this->createClassificationDetail($classification);
        $cabinet = StorageCabinet::query()->create([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-A1-01',
            'name' => 'Tu 1',
            'is_active' => true,
        ]);

        StorageSlot::query()->create([
            'storage_cabinet_id' => $cabinet->id,
            'classification_detail_id' => $detail->id,
            'slot_name' => 'Ngan B',
            'capacity' => 30,
            'current_quantity' => 0,
            'is_active' => true,
        ]);
        StorageSlot::query()->create([
            'storage_cabinet_id' => $cabinet->id,
            'classification_detail_id' => $detail->id,
            'slot_name' => 'Ngan A',
            'capacity' => 30,
            'current_quantity' => 0,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/storage-slots?sort=name_asc', $this->apiTokenHeaders($token));

        $response->assertStatus(200)->assertJsonPath('status', 'success');
        $rows = $response->json('data.data');
        $this->assertSame('Ngan A', $rows[0]['slot_name']);
        $this->assertSame('Ngan B', $rows[1]['slot_name']);
    }

    private function createWarehouse(): Warehouse
    {
        return Warehouse::query()->create([
            'code' => 'KHO-A1',
            'name' => 'Kho A1',
            'is_active' => true,
        ]);
    }

    private function createClassification(): Classification
    {
        return Classification::query()->create([
            'code' => 'CL-001',
            'name' => 'Khoa hoc may tinh',
        ]);
    }

    private function createClassificationDetail(Classification $classification): ClassificationDetail
    {
        return ClassificationDetail::query()->create([
            'classification_id' => $classification->id,
            'code' => 'CT-001',
            'name' => 'Lap trinh',
        ]);
    }
}

