<?php

namespace Tests\Feature\Backend;

use App\Enums\BookStatus;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Classification;
use App\Models\StorageCabinet;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageQuantitySyncCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_sync_quantities_command_updates_cabinet_counts(): void
    {
        $warehouse = Warehouse::query()->create([
            'code' => 'KHO-SYNC',
            'name' => 'Kho Sync',
            'is_active' => true,
        ]);
        $classification = Classification::query()->create([
            'code' => 'CL-SYNC',
            'name' => 'Phan loai sync',
        ]);
        $cabinet = StorageCabinet::query()->create([
            'warehouse_id' => $warehouse->id,
            'classification_id' => $classification->id,
            'code' => 'TU-SYNC-01',
            'name' => 'Tu sync',
            'current_quantity' => 0,
            'is_active' => true,
        ]);
        $book = Book::query()->create([
            'title' => 'Sach sync command',
            'resource_type' => 'textbook',
            'access_mode' => 'circulation_only',
            'quantity' => 2,
            'classification_id' => $classification->id,
            'warehouse_id' => $warehouse->id,
        ]);
        BookCopy::query()->create([
            'book_id' => $book->id,
            'barcode' => 'SYNC-001',
            'status' => BookStatus::AVAILABLE->value,
            'physical_condition' => 'good',
            'warehouse_id' => $warehouse->id,
        ]);
        BookCopy::query()->create([
            'book_id' => $book->id,
            'barcode' => 'SYNC-002',
            'status' => BookStatus::BORROWED->value,
            'physical_condition' => 'good',
            'warehouse_id' => $warehouse->id,
        ]);

        $this->artisan('storage:sync-quantities')
            ->assertSuccessful();

        $this->assertDatabaseHas('storage_cabinets', [
            'id' => $cabinet->id,
            'current_quantity' => 1,
        ]);
    }
}
