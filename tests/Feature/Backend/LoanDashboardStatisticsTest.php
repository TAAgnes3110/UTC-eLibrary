<?php

namespace Tests\Feature\Backend;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoanDashboardStatisticsTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    #[Test]
    public function statistics_includes_digital_purchase_summary(): void
    {
        [, $token] = $this->createAdminUserAndToken();
        $now = now();

        $userId = DB::table('users')->insertGetId([
            'user_type' => 'student',
            'email' => 'buyer-'.Str::lower(Str::random(6)).'@test.com',
            'password' => bcrypt('password'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $classificationId = DB::table('classifications')->insertGetId([
            'code' => 'C-STAT-01',
            'name' => 'Phân loại thống kê',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH-STAT-01',
            'name' => 'Kho thống kê',
            'is_active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $bookId = DB::table('books')->insertGetId([
            'title' => 'Sách số thống kê',
            'warehouse_id' => $warehouseId,
            'classification_id' => $classificationId,
            'quantity' => 0,
            'resource_type' => 'digital',
            'access_mode' => 'circulation_only',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $assetId = DB::table('digital_assets')->insertGetId([
            'book_id' => $bookId,
            'version' => 1,
            'is_primary' => 1,
            'storage_disk' => 'public',
            'path' => 'digital/stat.pdf',
            'original_name' => 'stat.pdf',
            'mime' => 'application/pdf',
            'byte_size' => 100,
            'visibility' => 'internal',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $paidOrderId = DB::table('orders')->insertGetId([
            'public_id' => (string) Str::uuid(),
            'user_id' => $userId,
            'type' => Order::TYPE_DIGITAL_PURCHASE,
            'status' => Order::STATUS_PAID,
            'subtotal_vnd_snapshot' => 5000,
            'total_vnd_snapshot' => 5000,
            'currency' => 'VND',
            'paid_at' => $now,
            'gateway' => Order::GATEWAY_SEPAY,
            'merchant_reference' => 'REF-PAID-'.Str::upper(Str::random(8)),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('order_items')->insert([
            'order_id' => $paidOrderId,
            'item_type' => 'digital_asset_unlock',
            'digital_asset_id' => $assetId,
            'quantity' => 1,
            'unit_price_vnd_snapshot' => 5000,
            'line_total_vnd_snapshot' => 5000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pendingOrderId = DB::table('orders')->insertGetId([
            'public_id' => (string) Str::uuid(),
            'user_id' => $userId,
            'type' => Order::TYPE_DIGITAL_PURCHASE,
            'status' => Order::STATUS_PENDING,
            'subtotal_vnd_snapshot' => 9000,
            'total_vnd_snapshot' => 9000,
            'currency' => 'VND',
            'gateway' => Order::GATEWAY_SEPAY,
            'merchant_reference' => 'REF-PEND-'.Str::upper(Str::random(8)),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('order_items')->insert([
            'order_id' => $pendingOrderId,
            'item_type' => 'digital_asset_unlock',
            'digital_asset_id' => $assetId,
            'quantity' => 3,
            'unit_price_vnd_snapshot' => 3000,
            'line_total_vnd_snapshot' => 9000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->getJson('/api/v1/loans/statistics?granularity=month&digital_granularity=day', $this->apiTokenHeaders($token));

        $response->assertOk()
            ->assertJsonPath('data.granularity', 'month')
            ->assertJsonPath('data.digital_granularity', 'day')
            ->assertJsonPath('data.summary.digital_books_purchased', 1)
            ->assertJsonPath('data.summary.digital_revenue_vnd', 5000)
            ->assertJsonStructure([
                'data' => [
                    'digital_series' => [
                        ['key', 'label', 'books_sold', 'revenue_vnd'],
                    ],
                ],
            ]);
    }
}
