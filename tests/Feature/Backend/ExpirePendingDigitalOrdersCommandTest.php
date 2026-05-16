<?php

namespace Tests\Feature\Backend;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpirePendingDigitalOrdersCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_expires_pending_orders_past_price_lock(): void
    {
        $userId = (int) User::factory()->create()->id;

        $publicId = (string) Str::uuid();
        DB::table('orders')->insert([
            'public_id' => $publicId,
            'user_id' => $userId,
            'type' => Order::TYPE_DIGITAL_PURCHASE,
            'status' => Order::STATUS_PENDING,
            'subtotal_vnd_snapshot' => 10000,
            'total_vnd_snapshot' => 10000,
            'currency' => 'VND',
            'price_locked_until' => now()->subMinute(),
            'gateway' => Order::GATEWAY_SEPAY,
            'merchant_reference' => 'DL'.strtoupper(Str::random(10)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('digital-orders:expire-pending');

        $this->assertSame(
            Order::STATUS_EXPIRED,
            (string) DB::table('orders')->where('public_id', $publicId)->value('status')
        );
    }

    #[Test]
    public function command_deletes_pending_orders_older_than_max_age_days(): void
    {
        config(['services.digital_orders.pending_max_age_days' => 3]);

        $userId = (int) User::factory()->create()->id;
        $oldPublicId = (string) Str::uuid();
        $recentPublicId = (string) Str::uuid();

        DB::table('orders')->insert([
            [
                'public_id' => $oldPublicId,
                'user_id' => $userId,
                'type' => Order::TYPE_DIGITAL_PURCHASE,
                'status' => Order::STATUS_PENDING,
                'subtotal_vnd_snapshot' => 10000,
                'total_vnd_snapshot' => 10000,
                'currency' => 'VND',
                'price_locked_until' => now()->addDay(),
                'gateway' => Order::GATEWAY_SEPAY,
                'merchant_reference' => 'DL'.strtoupper(Str::random(10)),
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'public_id' => $recentPublicId,
                'user_id' => $userId,
                'type' => Order::TYPE_DIGITAL_PURCHASE,
                'status' => Order::STATUS_PENDING,
                'subtotal_vnd_snapshot' => 10000,
                'total_vnd_snapshot' => 10000,
                'currency' => 'VND',
                'price_locked_until' => now()->addDay(),
                'gateway' => Order::GATEWAY_SEPAY,
                'merchant_reference' => 'DL'.strtoupper(Str::random(11)),
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        Artisan::call('digital-orders:expire-pending');

        $this->assertNull(DB::table('orders')->where('public_id', $oldPublicId)->first());
        $this->assertNotNull(DB::table('orders')->where('public_id', $recentPublicId)->first());
    }
}
