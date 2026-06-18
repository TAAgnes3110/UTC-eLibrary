<?php

namespace Tests\Feature\Notification;

use App\Enums\NotificationType;
use App\Enums\RoleType;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use App\Services\Notifications\StaffWorkQueueNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationServiceDedupeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function notify_updates_read_notification_with_same_dedupe_key_instead_of_inserting(): void
    {
        $admin = User::factory()->create(['user_type' => RoleType::ADMIN]);
        $service = app(NotificationService::class);
        $dedupeKey = 'admin.card_request_submitted:admin:'.$admin->id;

        $existing = $service->notify([
            'recipient_type' => Notification::RECIPIENT_ADMIN,
            'recipient_id' => $admin->id,
            'type' => NotificationType::ADMIN_CARD_REQUEST_SUBMITTED->value,
            'title' => 'Yêu cầu cấp thẻ thư viện',
            'message' => 'Hiện có 1 hồ sơ cấp thẻ chờ duyệt.',
            'dedupe_key' => $dedupeKey,
        ]);
        $existing->forceFill(['read_at' => now()])->save();

        $updated = $service->notify([
            'recipient_type' => Notification::RECIPIENT_ADMIN,
            'recipient_id' => $admin->id,
            'type' => NotificationType::ADMIN_CARD_REQUEST_SUBMITTED->value,
            'title' => 'Yêu cầu cấp thẻ thư viện',
            'message' => 'Hiện có 2 hồ sơ cấp thẻ chờ duyệt.',
            'dedupe_key' => $dedupeKey,
        ]);

        $this->assertSame($existing->id, $updated->id);
        $this->assertNull($updated->read_at);
        $this->assertSame('Hiện có 2 hồ sơ cấp thẻ chờ duyệt.', $updated->message);
        $this->assertSame(1, Notification::query()->where('dedupe_key', $dedupeKey)->count());
    }

    #[Test]
    public function staff_work_queue_sync_for_user_does_not_throw_when_digest_was_already_read(): void
    {
        $admin = User::factory()->create(['user_type' => RoleType::SUPER_ADMIN]);
        $dedupeKey = implode(':', [
            NotificationType::ADMIN_CARD_REQUEST_SUBMITTED->value,
            Notification::RECIPIENT_ADMIN,
            (string) $admin->id,
        ]);

        Notification::query()->create([
            'recipient_type' => Notification::RECIPIENT_ADMIN,
            'recipient_id' => $admin->id,
            'type' => NotificationType::ADMIN_CARD_REQUEST_SUBMITTED->value,
            'title' => 'Yêu cầu cấp thẻ thư viện',
            'message' => 'Cũ',
            'severity' => Notification::SEVERITY_INFO,
            'dedupe_key' => $dedupeKey,
            'read_at' => now(),
        ]);

        $this->expectNotToPerformAssertions();

        app(StaffWorkQueueNotificationService::class)->syncForUser($admin->fresh());
    }
}
