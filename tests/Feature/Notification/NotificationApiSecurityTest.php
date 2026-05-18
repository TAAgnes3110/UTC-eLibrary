<?php

namespace Tests\Feature\Notification;

use App\Enums\NotificationType;
use App\Enums\RoleType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    private function headers(string $token): array
    {
        return ['Authorization' => "Bearer $token", 'Accept' => 'application/json'];
    }

    private function seedNotification(string $recipientType, int $recipientId): Notification
    {
        return Notification::query()->create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'type' => NotificationType::USER_LOAN_DUE_SOON_REMINDER->value,
            'title' => 'Test',
            'message' => 'Nội dung test',
            'severity' => Notification::SEVERITY_INFO,
        ]);
    }

    #[Test]
    public function unauthenticated_cannot_list_notifications(): void
    {
        $this->getJson('/api/v1/me/notifications')->assertStatus(401);
    }

    #[Test]
    public function user_cannot_mark_another_users_notification_as_read(): void
    {
        $userA = User::factory()->create(['user_type' => RoleType::STUDENT, 'email' => 'notif-a@test.com']);
        $userB = User::factory()->create(['user_type' => RoleType::STUDENT, 'email' => 'notif-b@test.com']);

        $notif = $this->seedNotification(Notification::RECIPIENT_USER, (int) $userA->id);

        $response = $this->postJson(
            "/api/v1/me/notifications/{$notif->id}/read",
            [],
            $this->headers($this->tokenFor($userB))
        )->assertStatus(200);

        $this->assertFalse($response->json('data.marked'), 'Không được đánh dấu đọc thông báo của người khác.');
        $this->assertNull($notif->fresh()->read_at);
    }

    #[Test]
    public function user_cannot_delete_another_users_notification(): void
    {
        $userA = User::factory()->create(['user_type' => RoleType::STUDENT, 'email' => 'del-a@test.com']);
        $userB = User::factory()->create(['user_type' => RoleType::STUDENT, 'email' => 'del-b@test.com']);

        $notif = $this->seedNotification(Notification::RECIPIENT_USER, (int) $userA->id);

        $response = $this->postJson(
            "/api/v1/me/notifications/{$notif->id}/delete",
            [],
            $this->headers($this->tokenFor($userB))
        )->assertStatus(200);

        $this->assertFalse($response->json('data.deleted'));
        $this->assertNotNull(Notification::query()->find($notif->id));
    }

    #[Test]
    public function notification_list_with_invalid_severity_returns_422(): void
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);
        $token = $this->tokenFor($user);

        $this->getJson('/api/v1/me/notifications?severity=deadly', $this->headers($token))
            ->assertStatus(422);
    }

    #[Test]
    public function notification_list_with_per_page_over_100_returns_422(): void
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);
        $token = $this->tokenFor($user);

        $this->getJson('/api/v1/me/notifications?per_page=500', $this->headers($token))
            ->assertStatus(422);
    }

    #[Test]
    public function mark_read_with_negative_notification_id_does_not_mark_anything(): void
    {
        $user = User::factory()->create(['user_type' => RoleType::STUDENT]);
        $token = $this->tokenFor($user);

        $response = $this->postJson('/api/v1/me/notifications/-1/read', [], $this->headers($token))
            ->assertStatus(200);

        $this->assertFalse($response->json('data.marked'), 'ID âm không được coi là đã đọc thành công.');
    }
}
