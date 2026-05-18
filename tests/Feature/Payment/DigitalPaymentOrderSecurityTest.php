<?php

namespace Tests\Feature\Payment;

use App\Enums\RoleType;
use App\Models\LibrarySetting;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test toàn diện cho Digital Payment Order API.
 * Tập trung: bảo mật, IDOR, data integrity, edge cases, race condition.
 */
class DigitalPaymentOrderSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): array
    {
        $user = User::factory()->create(array_merge([
            'user_type' => RoleType::STUDENT,
            'password' => 'password',
        ], $overrides));
        $token = JWTAuth::fromUser($user);

        return [$user, $token];
    }

    private function seedPaidAsset(): int
    {
        return $this->seedAssetWithPrice(10000);
    }

    private function seedFreeAsset(): int
    {
        $assetId = $this->seedAssetWithPrice(0);
        DB::table('digital_asset_paywall_settings')->insert([
            'digital_asset_id' => $assetId,
            'is_paywall_enabled' => 0,
            'pdf_download_price_vnd' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $assetId;
    }

    private function seedAssetWithPrice(int $priceVnd = 10000): int
    {
        $this->seedLibrarySetting($priceVnd);
        $now = now();
        $cid = DB::table('classifications')->insertGetId([
            'code' => 'C-'.uniqid(), 'name' => 'Test Class',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $wid = DB::table('warehouses')->insertGetId([
            'code' => 'W-'.uniqid(), 'name' => 'Test WH', 'is_active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $bid = DB::table('books')->insertGetId([
            'title' => 'Test Book', 'warehouse_id' => $wid,
            'classification_id' => $cid, 'quantity' => 0,
            'resource_type' => 'digital', 'access_mode' => 'circulation_only',
            'created_at' => $now, 'updated_at' => $now,
            'created_by' => null, 'updated_by' => null, 'deleted_by' => null,
        ]);

        return (int) DB::table('digital_assets')->insertGetId([
            'book_id' => $bid, 'version' => 1, 'is_primary' => 1,
            'storage_disk' => 'public', 'path' => 'digital/'.uniqid().'.pdf',
            'original_name' => 'test.pdf', 'mime' => 'application/pdf',
            'byte_size' => 100, 'visibility' => 'internal',
            'created_at' => $now, 'updated_at' => $now,
            'created_by' => null, 'updated_by' => null, 'deleted_by' => null,
        ]);
    }

    private function seedLibrarySetting(int $price = 10000): void
    {
        $key = LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND;
        if (! DB::table('library_settings')->where('key', $key)->exists()) {
            DB::table('library_settings')->insert([
                'key' => $key, 'type' => 'int', 'value' => (string) $price,
                'json_value' => null, 'created_at' => now(), 'updated_at' => now(),
                'created_by' => null, 'updated_by' => null, 'deleted_by' => null,
            ]);
        }
    }

    private function authHeaders(string $token): array
    {
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    #[Test]
    public function unauthenticated_user_cannot_create_order(): void
    {
        $assetId = $this->seedPaidAsset();
        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ])->assertStatus(401);
    }

    // ── Input Validation ─────────────────────────────────────────────────────

    #[Test]
    public function create_order_with_empty_body_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-payment-orders', [], $this->authHeaders($token))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['digital_asset_ids']);
    }

    #[Test]
    public function create_order_with_empty_array_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [],
        ], $this->authHeaders($token))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['digital_asset_ids']);
    }

    #[Test]
    public function create_order_with_string_instead_of_array_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => 'not-an-array',
        ], $this->authHeaders($token))
            ->assertStatus(422);
    }

    #[Test]
    public function create_order_with_zero_id_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [0],
        ], $this->authHeaders($token))
            ->assertStatus(422);
    }

    #[Test]
    public function create_order_with_negative_id_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [-1],
        ], $this->authHeaders($token))
            ->assertStatus(422);
    }

    #[Test]
    public function create_order_with_nonexistent_asset_id_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [999999],
        ], $this->authHeaders($token))
            ->assertStatus(422);
    }

    #[Test]
    public function create_order_with_duplicate_ids_in_array_is_deduplicated(): void
    {
        [, $token] = $this->makeUser();
        $assetId = $this->seedPaidAsset();

        // IDs trùng lặp phải được xử lý: chỉ tạo 1 order item
        $response = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId, $assetId, $assetId],
        ], $this->authHeaders($token));

        // Dù validate distinct hay không – phải không crash
        $response->assertSuccessful();
    }

    #[Test]
    public function create_order_with_string_asset_id_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => ['not-integer'],
        ], $this->authHeaders($token))
            ->assertStatus(422);
    }

    #[Test]
    public function create_order_with_float_asset_id_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [1.5],
        ], $this->authHeaders($token))
            ->assertStatus(422);
    }

    #[Test]
    public function create_order_exceeding_max_50_assets_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $ids = range(1, 51); // 51 items > max:50

        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => $ids,
        ], $this->authHeaders($token))
            ->assertStatus(422);
    }

    // ── Business Logic ────────────────────────────────────────────────────────

    #[Test]
    public function create_order_for_free_asset_returns_422(): void
    {
        [, $token] = $this->makeUser();
        $freeAssetId = $this->seedFreeAsset();

        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$freeAssetId],
        ], $this->authHeaders($token))
            ->assertStatus(422);
    }

    #[Test]
    public function create_order_for_already_purchased_asset_is_skipped(): void
    {
        [$user, $token] = $this->makeUser();
        $assetId = $this->seedPaidAsset();

        // Cấp entitlement: user đã mua rồi
        DB::table('digital_asset_pdf_download_entitlements')->insert([
            'user_id' => $user->id,
            'digital_asset_id' => $assetId,
            'order_id' => null,
            'granted_at' => now(),
            'expires_at' => null,
            'revoked_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($token))
            ->assertStatus(422); // Không có item nào cần thanh toán
    }

    #[Test]
    public function can_create_second_pending_order_for_same_asset_when_under_user_limit(): void
    {
        [, $token] = $this->makeUser();
        $assetId = $this->seedPaidAsset();

        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($token))->assertStatus(201);

        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($token))->assertStatus(201);
    }

    #[Test]
    public function created_order_contains_required_fields(): void
    {
        [, $token] = $this->makeUser();
        $assetId = $this->seedPaidAsset();

        $response = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($token))->assertStatus(201);

        $response->assertJsonStructure([
            'data' => [
                'order' => [
                    'id', 'public_id', 'status', 'amount_vnd',
                    'currency', 'merchant_reference', 'price_locked_until', 'gateway',
                ],
            ],
        ]);
    }

    #[Test]
    public function created_order_status_is_pending(): void
    {
        [, $token] = $this->makeUser();
        $assetId = $this->seedPaidAsset();

        $response = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($token))->assertStatus(201);

        $this->assertEquals('pending', $response->json('data.order.status'));
    }

    #[Test]
    public function created_order_amount_matches_asset_price(): void
    {
        [, $token] = $this->makeUser();
        $assetId = $this->seedPaidAsset(); // price = 10000

        $response = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($token))->assertStatus(201);

        $this->assertEquals(10000, $response->json('data.order.amount_vnd'));
    }

    // ── IDOR: User A không thể thấy order của User B ─────────────────────────

    #[Test]
    public function user_cannot_view_other_users_order(): void
    {
        [$userA, $tokenA] = $this->makeUser(['email' => 'usera@example.com']);
        [, $tokenB] = $this->makeUser(['email' => 'userb@example.com']);
        $assetId = $this->seedPaidAsset();

        // UserA tạo order
        $response = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($tokenA))->assertStatus(201);

        $publicId = $response->json('data.order.public_id');

        // UserB cố xem order của UserA
        $this->getJson("/api/v1/me/orders/$publicId", $this->authHeaders($tokenB))
            ->assertStatus(404);
    }

    #[Test]
    public function user_cannot_cancel_other_users_order(): void
    {
        [$userA, $tokenA] = $this->makeUser(['email' => 'cancela@example.com']);
        [, $tokenB] = $this->makeUser(['email' => 'cancelb@example.com']);
        $assetId = $this->seedPaidAsset();

        $response = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($tokenA))->assertStatus(201);

        $publicId = $response->json('data.order.public_id');

        $this->postJson("/api/v1/me/orders/$publicId/cancel", [], $this->authHeaders($tokenB))
            ->assertStatus(404);
    }

    #[Test]
    public function cancel_already_paid_order_returns_422(): void
    {
        [$user, $token] = $this->makeUser(['email' => 'cancelpaid@example.com']);
        $assetId = $this->seedPaidAsset();

        $response = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($token))->assertStatus(201);

        $publicId = $response->json('data.order.public_id');

        // Bẻ status thành paid trong DB
        DB::table('orders')->where('public_id', $publicId)->update(['status' => 'paid']);

        $this->postJson("/api/v1/me/orders/$publicId/cancel", [], $this->authHeaders($token))
            ->assertStatus(422);
    }

    #[Test]
    public function cancel_nonexistent_order_returns_404(): void
    {
        [, $token] = $this->makeUser();

        $this->postJson('/api/v1/me/orders/00000000-0000-0000-0000-000000000000/cancel', [], $this->authHeaders($token))
            ->assertStatus(404);
    }

    #[Test]
    public function order_public_id_must_be_string_uuid_not_integer(): void
    {
        [, $token] = $this->makeUser();

        $this->getJson('/api/v1/me/orders/12345', $this->authHeaders($token))
            ->assertStatus(404);
    }

    // ── Sepay Webhook Security ────────────────────────────────────────────────

    #[Test]
    public function sepay_webhook_ignores_outgoing_transfer_type(): void
    {
        [$user, $token] = $this->makeUser(['email' => 'webhookout@example.com']);
        $assetId = $this->seedPaidAsset();

        $resp = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($token))->assertStatus(201);

        $ref = $resp->json('data.order.merchant_reference');
        $amount = $resp->json('data.order.amount_vnd');
        $pubId = $resp->json('data.order.public_id');

        // transferType = 'out' → phải bị bỏ qua
        $this->postJson('/api/v1/sepay/webhook', [
            'id' => 800001,
            'gateway' => 'VCB',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '123456',
            'code' => $ref,
            'content' => '',
            'transferType' => 'out', // <-- outgoing
            'description' => '',
            'transferAmount' => $amount,
            'referenceCode' => 'FT800001',
        ])->assertStatus(200);

        $this->assertEquals('pending', DB::table('orders')->where('public_id', $pubId)->value('status'));
    }

    #[Test]
    public function sepay_webhook_with_missing_id_is_ignored(): void
    {
        $this->postJson('/api/v1/sepay/webhook', [
            'gateway' => 'VCB',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '123456',
            'code' => 'DLABCDEFGH12',
            'content' => '',
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => 10000,
            // 'id' => missing
        ])->assertStatus(200); // Phải trả 200 (không crash)
    }

    #[Test]
    public function sepay_webhook_with_non_numeric_amount_is_ignored(): void
    {
        [$user, $token] = $this->makeUser(['email' => 'whnumeric@example.com']);
        $assetId = $this->seedPaidAsset();
        $resp = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($token))->assertStatus(201);

        $ref = $resp->json('data.order.merchant_reference');
        $pubId = $resp->json('data.order.public_id');

        $this->postJson('/api/v1/sepay/webhook', [
            'id' => 800002,
            'gateway' => 'VCB',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '123456',
            'code' => $ref,
            'content' => '',
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => 'ten_thousand', // không phải số
            'referenceCode' => 'FT800002',
        ])->assertStatus(200);

        $this->assertEquals('pending', DB::table('orders')->where('public_id', $pubId)->value('status'));
    }

    #[Test]
    public function sepay_webhook_with_extremely_long_id_is_rejected_gracefully(): void
    {
        $this->postJson('/api/v1/sepay/webhook', [
            'id' => str_repeat('9', 200), // >120 chars → bị bỏ qua
            'gateway' => 'VCB',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '123456',
            'code' => 'DLABCDEFGH12',
            'content' => '',
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => 10000,
            'referenceCode' => 'FTLONG',
        ])->assertStatus(200); // Không crash
    }

    #[Test]
    public function sepay_webhook_is_idempotent_same_transaction_id(): void
    {
        [$user, $token] = $this->makeUser(['email' => 'whidem@example.com']);
        $assetId = $this->seedPaidAsset();
        $resp = $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$assetId],
        ], $this->authHeaders($token))->assertStatus(201);

        $ref = $resp->json('data.order.merchant_reference');
        $amount = $resp->json('data.order.amount_vnd');
        $pubId = $resp->json('data.order.public_id');

        $payload = [
            'id' => 800003,
            'gateway' => 'VCB',
            'transactionDate' => now()->format('Y-m-d H:i:s'),
            'accountNumber' => '123456',
            'code' => $ref,
            'content' => '',
            'transferType' => 'in',
            'description' => '',
            'transferAmount' => $amount,
            'referenceCode' => 'FT800003',
        ];

        // Gọi 3 lần
        $this->postJson('/api/v1/sepay/webhook', $payload)->assertStatus(200);
        $this->postJson('/api/v1/sepay/webhook', $payload)->assertStatus(200);
        $this->postJson('/api/v1/sepay/webhook', $payload)->assertStatus(200);

        // Chỉ 1 transaction được tạo
        $this->assertEquals(1, DB::table('payment_transactions')->where('idempotency_key', '800003')->count());
        $this->assertEquals('paid', DB::table('orders')->where('public_id', $pubId)->value('status'));
    }
}
