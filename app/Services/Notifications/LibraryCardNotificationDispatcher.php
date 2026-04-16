<?php

namespace App\Services\Notifications;

use App\Enums\NotificationSeverity;
use App\Enums\NotificationType;
use App\Enums\RoleType;
use App\Models\LibraryCard;
use App\Models\Notification;
use App\Models\User;

/**
 * Thông báo nghiệp vụ đăng ký / duyệt thẻ thư viện (staff + bạn đọc có tài khoản).
 */
class LibraryCardNotificationDispatcher
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Gửi cho toàn bộ staff khi có hồ sơ cần xử lý (chờ duyệt hoặc chờ thanh toán).
     */
    public function notifyStaffOnNewCardApplication(LibraryCard $card): void
    {
        $workflow = (string) $card->workflow_status;
        if (! in_array($workflow, [
            LibraryCard::WORKFLOW_PENDING_REVIEW,
            LibraryCard::WORKFLOW_PENDING_PAYMENT,
        ], true)) {
            return;
        }

        $label = $workflow === LibraryCard::WORKFLOW_PENDING_PAYMENT
            ? 'chờ thanh toán lệ phí'
            : 'chờ duyệt hồ sơ';
        $who = trim((string) ($card->full_name ?? ''));
        if ($who === '') {
            $who = 'Bạn đọc';
        }

        $adminUsers = User::query()
            ->whereIn('user_type', RoleType::staffRoles())
            ->where('is_active', true)
            ->select(['id'])
            ->get();

        foreach ($adminUsers as $adminUser) {
            try {
                $this->notificationService->notify([
                    'recipient_type' => Notification::RECIPIENT_ADMIN,
                    'recipient_id' => (int) $adminUser->id,
                    'type' => NotificationType::ADMIN_CARD_REQUEST_SUBMITTED,
                    'title' => 'Có yêu cầu cấp thẻ thư viện',
                    'message' => sprintf('%s — %s (mã thẻ: %s).', $who, $label, (string) ($card->card_number ?? $card->code ?? '—')),
                    'severity' => NotificationSeverity::INFO,
                    'entity_type' => LibraryCard::class,
                    'entity_id' => (int) $card->id,
                    'action_url' => '/admin/library-cards/requests',
                    'dedupe_key' => $this->notificationService->buildEntityDedupeKey(
                        NotificationType::ADMIN_CARD_REQUEST_SUBMITTED,
                        Notification::RECIPIENT_ADMIN,
                        (int) $adminUser->id,
                        LibraryCard::class,
                        (int) $card->id
                    ),
                    'meta' => [
                        'workflow_status' => $workflow,
                        'holder_type' => (string) ($card->holder_type ?? ''),
                    ],
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public function notifyReaderCardApproved(LibraryCard $card): void
    {
        $userId = (int) ($card->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }

        try {
            $this->notificationService->notify([
                'recipient_type' => Notification::RECIPIENT_USER,
                'recipient_id' => $userId,
                'type' => NotificationType::USER_CARD_APPROVED,
                'title' => 'Thẻ thư viện đã được duyệt',
                'message' => sprintf('Hồ sơ thẻ của bạn đã được duyệt. Mã thẻ: %s.', (string) ($card->card_number ?? $card->code ?? '—')),
                'severity' => NotificationSeverity::INFO,
                'entity_type' => LibraryCard::class,
                'entity_id' => (int) $card->id,
                'action_url' => '/dich-vu/cap-the-thu-vien',
                'dedupe_key' => $this->notificationService->buildEntityDedupeKey(
                    NotificationType::USER_CARD_APPROVED,
                    Notification::RECIPIENT_USER,
                    $userId,
                    LibraryCard::class,
                    (int) $card->id
                ),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function notifyReaderCardRejected(LibraryCard $card, ?string $notes): void
    {
        $userId = (int) ($card->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }

        $msg = 'Hồ sơ đăng ký thẻ của bạn đã bị từ chối.';
        if ($notes !== null && trim($notes) !== '') {
            $msg .= ' Ghi chú: '.trim($notes);
        }

        try {
            $this->notificationService->notify([
                'recipient_type' => Notification::RECIPIENT_USER,
                'recipient_id' => $userId,
                'type' => NotificationType::USER_CARD_REJECTED,
                'title' => 'Thẻ thư viện bị từ chối',
                'message' => $msg,
                'severity' => NotificationSeverity::WARNING,
                'entity_type' => LibraryCard::class,
                'entity_id' => (int) $card->id,
                'action_url' => '/dich-vu/cap-the-thu-vien',
                'dedupe_key' => $this->notificationService->buildEntityDedupeKey(
                    NotificationType::USER_CARD_REJECTED,
                    Notification::RECIPIENT_USER,
                    $userId,
                    LibraryCard::class,
                    (int) $card->id
                ),
                'meta' => ['review_notes' => $notes],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
