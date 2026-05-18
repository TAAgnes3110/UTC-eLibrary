<?php

namespace Tests\Feature\Backend;

use App\Models\LibrarySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalPaymentOrderDuplicatePendingTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    private function seedBookWithDigitalAsset(string $suffix = '01'): int
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
            'code' => 'C-DPAY-DUP-'.$suffix,
            'name' => 'Phân loại test đơn pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH-DPAY-DUP-'.$suffix,
            'name' => 'Kho test đơn pending',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $bookId = DB::table('books')->insertGetId([
            'title' => 'Luận văn test đơn pending '.$suffix,
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
            'path' => 'digital/test-dup-pending-'.$suffix.'.pdf',
            'original_name' => 'test-'.$suffix.'.pdf',
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
    public function can_create_second_pending_order_for_same_digital_asset_when_under_limit(): void
    {
        Config::set('services.digital_orders.pending_max_per_user', 3);

        [, $token] = $this->createUserAndToken();
        $assetId = $this->seedBookWithDigitalAsset();

        $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        )->assertStatus(201);

        $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetId]],
            $this->apiTokenHeaders($token)
        )->assertStatus(201);
    }

    #[Test]
    public function cannot_exceed_max_pending_orders_per_user(): void
    {
        Config::set('services.digital_orders.pending_max_per_user', 2);

        [, $token] = $this->createUserAndToken();
        $assetA = $this->seedBookWithDigitalAsset('A');
        $assetB = $this->seedBookWithDigitalAsset('B');
        $assetC = $this->seedBookWithDigitalAsset('C');

        $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetA]],
            $this->apiTokenHeaders($token)
        )->assertStatus(201);

        $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetB]],
            $this->apiTokenHeaders($token)
        )->assertStatus(201);

        $third = $this->postJson(
            '/api/v1/me/digital-payment-orders',
            ['digital_asset_ids' => [$assetC]],
            $this->apiTokenHeaders($token)
        );
        $third->assertStatus(422);
        $third->assertJsonFragment(['messages' => 'Bạn đang có 2 đơn chờ thanh toán (giới hạn 2). Vui lòng hoàn tất hoặc hủy bớt đơn cũ trước khi tạo đơn mới.']);
    }
}
