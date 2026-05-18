<?php

namespace Tests\Feature\Modules;

use App\Enums\NotificationType;
use App\Enums\RoleType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ModuleTestHelpers;
use Tests\TestCase;

/**
 * Module: Thông báo (10 case).
 */
class NotificationModuleTest extends TestCase
{
    use ModuleTestHelpers;
    use RefreshDatabase;

    private function seedFor(User $user): Notification
    {
        return Notification::query()->create([
            'recipient_type' => Notification::RECIPIENT_USER,
            'recipient_id' => $user->id,
            'type' => NotificationType::USER_LOAN_DUE_SOON_REMINDER->value,
            'title' => 'Nhắc hạn',
            'message' => 'Test',
            'severity' => Notification::SEVERITY_INFO,
        ]);
    }

    #[Test]
    public function case01_unauthenticated_returns_401(): void
    {
        $this->getJson('/api/v1/me/notifications')->assertStatus(401);
    }

    #[Test]
    public function case02_list_returns_200(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/notifications', $h)->assertSuccessful();
    }

    #[Test]
    public function case03_invalid_severity_returns_422(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/notifications?severity=deadly', $h)->assertStatus(422);
    }

    #[Test]
    public function case04_mark_own_notification_read(): void
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);
        $n = $this->seedFor($user);
        $token = JWTAuth::fromUser($user);
        $r = $this->postJson("/api/v1/me/notifications/{$n->id}/read", [], $this->bearer($token));
        $this->assertTrue($r->json('data.marked'));
    }

    #[Test]
    public function case05_cannot_mark_others_notification(): void
    {
        $a = User::factory()->create(['email' => 'na@t.com']);
        $b = User::factory()->create(['email' => 'nb@t.com']);
        $n = $this->seedFor($a);
        $tokenB = JWTAuth::fromUser($b);
        $r = $this->postJson("/api/v1/me/notifications/{$n->id}/read", [], $this->bearer($tokenB));
        $this->assertFalse($r->json('data.marked'));
    }

    #[Test]
    public function case06_read_all_returns_200(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/notifications/read-all', [], $h)->assertSuccessful();
    }

    #[Test]
    public function case07_delete_all_returns_200(): void
    {
        [, $h] = $this->studentContext();
        $this->postJson('/api/v1/me/notifications/delete-all', [], $h)->assertSuccessful();
    }

    #[Test]
    public function case08_delete_own_notification(): void
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);
        $n = $this->seedFor($user);
        $token = JWTAuth::fromUser($user);
        $r = $this->postJson("/api/v1/me/notifications/{$n->id}/delete", [], $this->bearer($token));
        $this->assertTrue($r->json('data.deleted'));
    }

    #[Test]
    public function case09_per_page_over_100_returns_422(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/me/notifications?per_page=500', $h)->assertStatus(422);
    }

    #[Test]
    public function case10_nonexistent_notification_mark_read_returns_marked_false(): void
    {
        [, $h] = $this->studentContext();
        $r = $this->postJson('/api/v1/me/notifications/9999999/read', [], $h)->assertStatus(200);
        $this->assertFalse($r->json('data.marked'));
    }
}
