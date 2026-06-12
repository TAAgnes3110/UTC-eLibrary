<?php

namespace App\Services\Notifications;

use App\Enums\NotificationSeverity;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserProfileUpdateRequest;

/**
 * Thông báo yêu cầu cập nhật hồ sơ (staff + bạn đọc).
 */
class UserProfileUpdateNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly StaffWorkQueueNotificationService $staffWorkQueueNotificationService
    ) {}

    public function notifyAdminsProfileReviewNeeded(UserProfileUpdateRequest $record, User $requester): void
    {
        try {
            $this->staffWorkQueueNotificationService->syncForAllActiveStaff();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function notifyUserProfileRequestReviewed(UserProfileUpdateRequest $record, bool $approved): void
    {
        $type = $approved
            ? NotificationType::USER_PROFILE_UPDATE_APPROVED
            : NotificationType::USER_PROFILE_UPDATE_REJECTED;
        $title = $approved
            ? 'Yêu cầu cập nhật hồ sơ đã được duyệt'
            : 'Yêu cầu cập nhật hồ sơ bị từ chối';
        $message = $approved
            ? 'Thông tin mã định danh/khoa/niên khóa/lớp của bạn đã được cập nhật.'
            : 'Yêu cầu cập nhật hồ sơ của bạn đã bị từ chối. Vui lòng kiểm tra ghi chú phản hồi.';
        $severity = $approved ? NotificationSeverity::INFO : NotificationSeverity::WARNING;

        try {
            $this->notificationService->notify([
                'recipient_type' => Notification::RECIPIENT_USER,
                'recipient_id' => (int) $record->user_id,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'severity' => $severity,
                'entity_type' => UserProfileUpdateRequest::class,
                'entity_id' => (int) $record->id,
                'action_url' => '/tai-khoan/lich-su-yeu-cau-cap-nhat',
                'dedupe_key' => $this->notificationService->buildEntityDedupeKey(
                    $type,
                    Notification::RECIPIENT_USER,
                    (int) $record->user_id,
                    UserProfileUpdateRequest::class,
                    (int) $record->id
                ),
                'meta' => [
                    'status' => $record->status,
                    'review_note' => $record->review_note,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
