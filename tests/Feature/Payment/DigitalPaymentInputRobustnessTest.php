<?php

namespace Tests\Feature\Payment;

use App\Enums\RoleType;
use App\Models\LibrarySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DigitalPaymentInputRobustnessTest extends TestCase
{
    use RefreshDatabase;

    private function userToken(): array
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);
        $token = JWTAuth::fromUser($user);

        return [$user, $token];
    }

    private function headers(string $token): array
    {
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    private function seedAsset(): int
    {
        $key = LibrarySetting::KEY_DIGITAL_DEFAULT_PDF_DOWNLOAD_PRICE_VND;
        if (! DB::table('library_settings')->where('key', $key)->exists()) {
            DB::table('library_settings')->insert([
                'key' => $key, 'type' => 'int', 'value' => '10000',
                'json_value' => null, 'created_at' => now(), 'updated_at' => now(),
                'created_by' => null, 'updated_by' => null, 'deleted_by' => null,
            ]);
        }
        $now = now();
        $cid = DB::table('classifications')->insertGetId([
            'code' => 'C-'.uniqid(), 'name' => 'Pay', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $wid = DB::table('warehouses')->insertGetId([
            'code' => 'W-'.uniqid(), 'name' => 'Pay', 'is_active' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $bid = DB::table('books')->insertGetId([
            'title' => 'Pay Book', 'warehouse_id' => $wid, 'classification_id' => $cid,
            'quantity' => 0, 'resource_type' => 'digital', 'access_mode' => 'circulation_only',
            'created_at' => $now, 'updated_at' => $now,
            'created_by' => null, 'updated_by' => null, 'deleted_by' => null,
        ]);

        return (int) DB::table('digital_assets')->insertGetId([
            'book_id' => $bid, 'version' => 1, 'is_primary' => 1,
            'storage_disk' => 'public', 'path' => 'digital/'.uniqid().'.pdf',
            'original_name' => 'x.pdf', 'mime' => 'application/pdf', 'byte_size' => 1,
            'visibility' => 'internal',
            'created_at' => $now, 'updated_at' => $now,
            'created_by' => null, 'updated_by' => null, 'deleted_by' => null,
        ]);
    }

    #[Test]
    public function create_order_with_duplicate_asset_ids_returns_422(): void
    {
        [, $token] = $this->userToken();
        $id = $this->seedAsset();

        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => [$id, $id],
        ], $this->headers($token))->assertStatus(422);
    }

    #[Test]
    public function create_order_with_more_than_50_assets_returns_422(): void
    {
        [, $token] = $this->userToken();
        $ids = range(1, 51);

        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => $ids,
        ], $this->headers($token))->assertStatus(422);
    }

    #[Test]
    public function create_order_with_string_asset_id_returns_422(): void
    {
        [, $token] = $this->userToken();

        $this->postJson('/api/v1/me/digital-payment-orders', [
            'digital_asset_ids' => ['not-int'],
        ], $this->headers($token))->assertStatus(422);
    }

    #[Test]
    public function order_show_with_invalid_public_id_format_returns_404(): void
    {
        [, $token] = $this->userToken();

        $this->getJson('/api/v1/me/orders/not-a-valid-uuid', $this->headers($token))
            ->assertStatus(404);
    }

    #[Test]
    public function order_show_sync_flag_with_non_boolean_is_coerced_or_rejected(): void
    {
        [, $token] = $this->userToken();

        $response = $this->getJson(
            '/api/v1/me/orders/00000000-0000-0000-0000-000000000001?sync=maybe',
            $this->headers($token)
        );

        $this->assertContains($response->status(), [404, 410]);
    }
}
