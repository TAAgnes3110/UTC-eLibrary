<?php

namespace Tests\Feature\Backend;

use App\Models\Classification;
use App\Models\StorageCabinet;
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

    private function createCabinet(Warehouse $warehouse, Classification $classification, array $overrides = []): StorageCabinet
    {
        return StorageCabinet::query()->create(array_merge([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-A1-01',
            'name' => 'Tu A1',
            'current_quantity' => 0,
            'is_active' => true,
        ], $overrides));
    }
}
