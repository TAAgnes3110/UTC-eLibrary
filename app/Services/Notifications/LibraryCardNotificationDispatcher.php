<?php

namespace App\Services\Notifications;

use App\Mail\LibraryCardPickupReminderMail;
use App\Enums\NotificationSeverity;
use App\Enums\NotificationType;
use App\Models\LibraryCard;
use App\Models\Notification;
use Illuminate\Support\Facades\Mail;

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
        // Chỉ dùng thông báo tổng hợp theo số lượng cho staff (sync tại StaffWorkQueueNotificationService (được AuthController gọi)).
    }

    public function notifyReaderCardApproved(LibraryCard $card): void
    {
        $this->notifyReaderCardPickupReminder($card, 3);
    }

    /**
     * Gửi nhắc nhận thẻ sau khi thủ thư duyệt/cấp tại quầy:
     * - Chuông thông báo trong web
     * - Email tới bạn đọc
     */
    public function notifyReaderCardPickupReminder(LibraryCard $card, int $pickupWithinDays = 3): void
    {
        $userId = (int) ($card->user_id ?? 0);
        if ($userId <= 0) return;

        $pickupWithinDays = max(1, $pickupWithinDays);
        $cardCode = (string) ($card->card_number ?? $card->code ?? '—');
        $message = sprintf(
            'Thẻ thư viện của bạn đã được duyệt/cấp. Vui lòng đến thư viện nhận thẻ trong vòng %d ngày. Mã thẻ: %s.',
            $pickupWithinDays,
            $cardCode
        );

        try {
            $this->notificationService->notify([
                'recipient_type' => Notification::RECIPIENT_USER,
                'recipient_id' => $userId,
                'type' => NotificationType::USER_CARD_APPROVED,
                'title' => 'Thẻ thư viện đã sẵn sàng nhận',
                'message' => $message,
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

        try {
            $email = trim((string) ($card->email ?? ''));
            if ($email !== '') {
                Mail::to($email)->send(new LibraryCardPickupReminderMail($card, $pickupWithinDays));
            }
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
