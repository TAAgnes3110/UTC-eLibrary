<?php

namespace Tests\Feature\Backend;

use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\StorageCabinet;
use App\Models\StorageSlot;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageCabinetApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    public function test_storage_cabinets_index_returns_paginated_data(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $warehouse = $this->createWarehouse();
        $classification = $this->createClassification();

        StorageCabinet::query()->create([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-GT-01',
            'name' => 'Khoa hoc may tinh',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/storage-cabinets?per_page=20', $this->apiTokenHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    'data' => [
                        ['id', 'warehouse_id', 'classification_id', 'code', 'name'],
                    ],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);
    }

    public function test_storage_slots_index_returns_paginated_slot_rows(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $warehouse = $this->createWarehouse();
        $classification = $this->createClassification();
        $detail = $this->createClassificationDetail($classification);
        $cabinet = $this->createCabinet($warehouse, $classification);

        $slot = StorageSlot::query()->create([
            'storage_cabinet_id' => $cabinet->id,
            'classification_detail_id' => $detail->id,
            'slot_name' => 'Ngan A1',
            'capacity' => 30,
            'current_quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/storage-slots?per_page=20', $this->apiTokenHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.data.0.id', $slot->id)
            ->assertJsonPath('data.data.0.cabinet.id', $cabinet->id)
            ->assertJsonPath('data.data.0.classification_detail.id', $detail->id);
    }

    public function test_create_storage_cabinet_generates_code_by_warehouse(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $warehouse = $this->createWarehouse(['code' => 'KHO-GT']);
        $classification = $this->createClassification();

        $response = $this->postJson('/api/v1/storage-cabinets', [
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'name' => 'Tu giao trinh',
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.code', 'TU-GT-01');
    }

    public function test_create_storage_slot_uses_default_capacity_and_custom_name(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $warehouse = $this->createWarehouse();
        $classification = $this->createClassification();
        $detail = $this->createClassificationDetail($classification);
        $cabinet = $this->createCabinet($warehouse, $classification);

        $response = $this->postJson("/api/v1/storage-cabinets/{$cabinet->id}/slots", [
            'classification_detail_id' => $detail->id,
            'slot_name' => 'Ngan Tu Nhap',
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.slot_name', 'Ngan Tu Nhap')
            ->assertJsonPath('data.capacity', 30);
    }

    public function test_update_storage_slot_persists_custom_name(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $warehouse = $this->createWarehouse();
        $classification = $this->createClassification();
        $detail = $this->createClassificationDetail($classification);
        $cabinet = $this->createCabinet($warehouse, $classification);
        $slot = StorageSlot::query()->create([
            'storage_cabinet_id' => $cabinet->id,
            'classification_detail_id' => $detail->id,
            'slot_name' => 'Ngan Cu',
            'capacity' => 30,
            'current_quantity' => 0,
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/v1/storage-cabinets/{$cabinet->id}/slots/{$slot->id}", [
            'classification_detail_id' => $detail->id,
            'slot_name' => 'Ngan Moi',
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.slot_name', 'Ngan Moi');
    }

    public function test_create_storage_slot_rejects_detail_not_matching_cabinet_classification(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $warehouse = $this->createWarehouse();
        $classificationA = $this->createClassification(['code' => 'CL-A', 'name' => 'CL A']);
        $classificationB = $this->createClassification(['code' => 'CL-B', 'name' => 'CL B']);
        $detailOfB = $this->createClassificationDetail($classificationB, ['code' => 'CT-B-01', 'name' => 'CT B 01']);
        $cabinet = $this->createCabinet($warehouse, $classificationA);

        $response = $this->postJson("/api/v1/storage-cabinets/{$cabinet->id}/slots", [
            'classification_detail_id' => $detailOfB->id,
            'slot_name' => 'Ngan Sai CL',
        ], $this->apiTokenHeaders($token));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    private function createWarehouse(array $overrides = []): Warehouse
    {
        return Warehouse::query()->create(array_merge([
            'code' => 'KHO-A1',
            'name' => 'Kho A1',
            'is_active' => true,
        ], $overrides));
    }

    private function createClassification(array $overrides = []): Classification
    {
        return Classification::query()->create(array_merge([
            'code' => 'CL-001',
            'name' => 'Khoa hoc may tinh',
        ], $overrides));
    }

    private function createClassificationDetail(Classification $classification, array $overrides = []): ClassificationDetail
    {
        return ClassificationDetail::query()->create(array_merge([
            'classification_id' => $classification->id,
            'code' => 'CT-001',
            'name' => 'Lap trinh',
        ], $overrides));
    }

    private function createCabinet(Warehouse $warehouse, Classification $classification, array $overrides = []): StorageCabinet
    {
        return StorageCabinet::query()->create(array_merge([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-A1-01',
            'name' => 'Tu A1',
            'capacity_total' => 0,
            'current_quantity' => 0,
            'is_active' => true,
        ], $overrides));
    }
}

