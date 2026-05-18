<?php

namespace Tests\Feature\Webhook;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Webhook SePay — xác thực, đầu vào, không xử lý khi thiếu secret (production).
 */
class SepayWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function webhook_rejects_request_when_secret_configured_but_token_missing(): void
    {
        config(['services.sepay.webhook_secret' => 'unit-test-webhook-secret']);

        $this->postJson('/api/v1/sepay/webhook', ['id' => 1, 'transferAmount' => 1000])
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    #[Test]
    public function webhook_rejects_wrong_bearer_token_when_secret_configured(): void
    {
        config(['services.sepay.webhook_secret' => 'unit-test-webhook-secret']);

        $this->postJson('/api/v1/sepay/webhook', ['id' => 2], [
            'Authorization' => 'Bearer wrong-token',
        ])->assertStatus(401);
    }

    #[Test]
    public function webhook_accepts_valid_bearer_when_secret_configured(): void
    {
        config(['services.sepay.webhook_secret' => 'unit-test-webhook-secret']);

        $this->postJson('/api/v1/sepay/webhook', [
            'id' => 3,
            'transferAmount' => 0,
            'content' => 'no matching ref',
        ], [
            'Authorization' => 'Bearer unit-test-webhook-secret',
        ])->assertStatus(200)->assertJson(['success' => true]);
    }

    #[Test]
    public function webhook_with_empty_secret_should_not_accept_unauthenticated_requests_in_production(): void
    {
        config(['services.sepay.webhook_secret' => '']);
        $this->app['env'] = 'production';

        $response = $this->postJson('/api/v1/sepay/webhook', [
            'id' => 4,
            'transferAmount' => 999999,
            'content' => 'fake payment',
        ]);

        $this->assertContains(
            $response->status(),
            [401, 403, 503],
            'Production không được xử lý webhook khi chưa cấu hình SEPAY_WEBHOOK_SECRET.'
        );
    }

    #[Test]
    public function webhook_with_extremely_large_json_body_does_not_crash(): void
    {
        config(['services.sepay.webhook_secret' => 'unit-test-webhook-secret']);

        $payload = [
            'id' => 5,
            'transferAmount' => 1000,
            'content' => str_repeat('A', 50000),
        ];

        $this->postJson('/api/v1/sepay/webhook', $payload, [
            'Authorization' => 'Bearer unit-test-webhook-secret',
        ])->assertStatus(200);
    }
}
