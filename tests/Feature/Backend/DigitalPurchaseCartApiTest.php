<?php

namespace Tests\Feature\Backend;

use App\Models\LibrarySetting;
use App\Services\LibrarySettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalPurchaseCartApiTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    /**
     * @return array{0: int, 1: int}
     */
    private function makeBookDeps(): array
    {
        $now = now();
        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'C-DPCART-01',
            'name' => 'Phân loại test giỏ DB',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH-DPCART-01',
            'name' => 'Kho test giỏ DB',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$classificationId, $warehouseId];
    }

    #[Test]
    public function reader_digital_cart_stores_price_in_db_and_list_returns_it(): void
    {
        [$user, $token] = $this->createUserAndToken();
        [$classificationId, $warehouseId] = $this->makeBookDeps();
        $now = now();

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

        $bookId = DB::table('books')->insertGetId([
            'title' => 'Luận văn giỏ DB',
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

        $assetId = DB::table('digital_assets')->insertGetId([
            'book_id' => $bookId,
            'version' => 1,
            'is_primary' => 1,
            'storage_disk' => 'public',
            'path' => 'digital/test-cart-db.pdf',
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

        $add = $this->postJson(
            '/api/v1/me/digital-purchase-cart/items',
            [
                'digital_asset_id' => $assetId,
                'book_id' => $bookId,
                'book_title' => 'Luận văn giỏ DB',
                'file_name' => 'test.pdf',
            ],
            $this->apiTokenHeaders($token)
        );
        $add->assertStatus(201)->assertJsonPath('status', 'success');

        $this->assertSame(
            '10000',
            (string) DB::table('cart_items')->where('digital_asset_id', $assetId)->value('unit_price_vnd_snapshot')
        );

        $list = $this->getJson('/api/v1/me/digital-purchase-cart', $this->apiTokenHeaders($token));
        $list->assertStatus(200)->assertJsonPath('data.items.0.price_vnd', 10000);

        $count = $this->getJson('/api/v1/me/digital-purchase-cart/count', $this->apiTokenHeaders($token));
        $count->assertStatus(200)->assertJsonPath('data.count', 1);

        DB::table('library_settings')
            ->where('key', LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND)
            ->update(['value' => '2000', 'updated_at' => now()]);
        app(LibrarySettingsService::class)->clearCache();

        $listAfterPriceChange = $this->getJson('/api/v1/me/digital-purchase-cart', $this->apiTokenHeaders($token));
        $listAfterPriceChange->assertStatus(200)->assertJsonPath('data.items.0.price_vnd', 2000);
        $this->assertSame(
            '2000',
            (string) DB::table('cart_items')->where('digital_asset_id', $assetId)->value('unit_price_vnd_snapshot')
        );

        $del = $this->deleteJson("/api/v1/me/digital-purchase-cart/items/{$assetId}", [], $this->apiTokenHeaders($token));
        $del->assertStatus(200);

        $this->assertSame(0, (int) DB::table('cart_items')->where('digital_asset_id', $assetId)->count());
    }
}
