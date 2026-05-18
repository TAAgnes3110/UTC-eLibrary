<?php

namespace Tests\Feature\Modules;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Module: Webhook SePay (10 case).
 */
class WebhookModuleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function case01_no_secret_no_token_still_returns_200_today(): void
    {
        config(['services.sepay.webhook_secret' => '']);
        $this->postJson('/api/v1/sepay/webhook', ['id' => 1])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function case02_with_secret_missing_token_returns_401(): void
    {
        config(['services.sepay.webhook_secret' => 'test-secret']);
        $this->postJson('/api/v1/sepay/webhook', ['id' => 2])->assertStatus(401);
    }

    #[Test]
    public function case03_with_secret_wrong_token_returns_401(): void
    {
        config(['services.sepay.webhook_secret' => 'test-secret']);
        $this->postJson('/api/v1/sepay/webhook', ['id' => 3], [
            'Authorization' => 'Bearer wrong',
        ])->assertStatus(401);
    }

    #[Test]
    public function case04_with_secret_valid_token_returns_200(): void
    {
        config(['services.sepay.webhook_secret' => 'test-secret']);
        $this->postJson('/api/v1/sepay/webhook', ['id' => 4, 'transferAmount' => 0], [
            'Authorization' => 'Bearer test-secret',
        ])->assertJson(['success' => true]);
    }

    #[Test]
    public function case05_webhook_accepts_x_sepay_header(): void
    {
        config(['services.sepay.webhook_secret' => 'hdr-secret']);
        $this->postJson('/api/v1/sepay/webhook', ['id' => 5], [
            'X-Sepay-Webhook-Token' => 'hdr-secret',
        ])->assertStatus(200);
    }

    #[Test]
    public function case06_empty_payload_still_success_json(): void
    {
        config(['services.sepay.webhook_secret' => 'test-secret']);
        $this->postJson('/api/v1/sepay/webhook', [], [
            'Authorization' => 'Bearer test-secret',
        ])->assertJson(['success' => true]);
    }

    #[Test]
    public function case07_large_content_does_not_crash(): void
    {
        config(['services.sepay.webhook_secret' => 'test-secret']);
        $this->postJson('/api/v1/sepay/webhook', [
            'id' => 7, 'content' => str_repeat('x', 100000),
        ], ['Authorization' => 'Bearer test-secret'])->assertStatus(200);
    }

    #[Test]
    public function case08_production_empty_secret_should_reject(): void
    {
        config(['services.sepay.webhook_secret' => '']);
        $this->app['env'] = 'production';
        $status = $this->postJson('/api/v1/sepay/webhook', ['id' => 8])->status();
        $this->assertContains($status, [401, 403, 503], 'Mong đợi: production không xử lý webhook khi thiếu secret.');
    }

    #[Test]
    public function case09_negative_transfer_amount_handled(): void
    {
        config(['services.sepay.webhook_secret' => 'test-secret']);
        $this->postJson('/api/v1/sepay/webhook', [
            'id' => 9, 'transferAmount' => -100,
        ], ['Authorization' => 'Bearer test-secret'])->assertStatus(200);
    }

    #[Test]
    public function case10_response_is_json_not_html(): void
    {
        config(['services.sepay.webhook_secret' => 'test-secret']);
        $r = $this->postJson('/api/v1/sepay/webhook', ['id' => 10], [
            'Authorization' => 'Bearer test-secret',
        ]);
        $this->assertStringContainsString('application/json', $r->headers->get('Content-Type') ?? '');
    }
}
