<?php

namespace Tests\Feature\Backend;

use App\Models\Classification;
use App\Models\StorageCabinet;
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

    public function test_storage_cabinets_payload_does_not_include_slots_key(): void
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

        $response = $this->getJson('/api/v1/storage-cabinets', $this->apiTokenHeaders($token));

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $first = $response->json('data.data.0');
        $this->assertArrayNotHasKey('slots', $first);
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

}

