<?php

namespace Tests\Feature\Backend;

use App\Models\LibrarySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalPaymentOrderDuplicatePendingTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    private function seedBookWithDigitalAsset(): int
    {
        $now = now();

        if (! DB::table('library_settings')->where('key', LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND)->exists()) {
            DB::table('library_settings')->insert([
                'key' => LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND,
                'type' => 'int',
                'value' => '10000',
                'json_value' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => null,
                'updated_by' => null,
                'deleted_by' => null,
            ]);
        }

        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'C-DPAY-DUP-01',
            'name' => 'Phân loại test đơn trùng',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH-DPAY-DUP-01',
            'name' => 'Kho test đơn trùng',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $bookId = DB::table('books')->insertGetId([
            'title' => 'Luận văn test đơn trùng',
            'warehouse_id' => $warehouseId,
            'classification_id' => $classificationId,
            'quantity' => 0,
            'resource_type' => 'digital',
            'access_mode' => 'circulation_only',
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ]);

        return (int) DB::table('digital_assets')->insertGetId([
            'book_id' => $bookId,
            'version' => 1,
            'is_primary' => 1,
            'storage_disk' => 'public',
            'path' => 'digital/test-dup-pending.pdf',
            'original_name' => 'test.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
        ]);
    }

    #[Test]
    public function cannot_create_second_pending_order_for_same_digital_asset(): void
    {
        [, $token] = $this->createUserAndToken();
        $assetId = $this->seedBookWithDigitalAsset();

        $first = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        );
        $first->assertStatus(201);

        $second = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        );
        $second->assertStatus(422);
    }
}
