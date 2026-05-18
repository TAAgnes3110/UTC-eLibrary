<?php

namespace App\Services\Notifications;

use App\Enums\NotificationSeverity;
use App\Enums\NotificationType;
use App\Models\DigitalDocumentSubmission;
use App\Models\Notification;

class DigitalDocumentSubmissionNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly StaffWorkQueueNotificationService $staffWorkQueueNotificationService,
    ) {}

    /** Cập nhật digest hàng chờ cho toàn bộ staff khi có đồ án/luận văn mới chờ duyệt. */
    public function notifyStaffPendingReview(): void
    {
        try {
            $this->staffWorkQueueNotificationService->syncForAllActiveStaff();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function notifySubmitterReviewed(DigitalDocumentSubmission $submission, bool $approved): void
    {
        $userId = (int) $submission->submitted_by;
        if ($userId <= 0) {
            return;
        }

        $submission->loadMissing('approvedBook:id,title');

        $titleLabel = trim((string) $submission->title);
        if ($titleLabel === '') {
            $titleLabel = trim((string) ($submission->original_name ?? '')) ?: 'Tài liệu số';
        }

        if ($approved) {
            $type = NotificationType::USER_DIGITAL_DOCUMENT_SUBMISSION_APPROVED;
            $title = 'Đồ án, luận văn đã được duyệt';
            $message = sprintf(
                '«%s» đã được thư viện duyệt và đăng tải lên tra cứu.',
                $titleLabel
            );
            $severity = NotificationSeverity::INFO;
            $actionUrl = $submission->approved_book_id
                ? '/tra-cuu-sach/'.(int) $submission->approved_book_id
                : '/dich-vu/tai-lieu-so';
        } else {
            $type = NotificationType::USER_DIGITAL_DOCUMENT_SUBMISSION_REJECTED;
            $title = 'Đồ án, luận văn bị từ chối';
            $message = sprintf('«%s» chưa được duyệt.', $titleLabel);
            $note = trim((string) ($submission->review_note ?? ''));
            if ($note !== '') {
                $message .= ' Ghi chú: '.$note;
            }
            $severity = NotificationSeverity::WARNING;
            $actionUrl = '/dich-vu/tai-lieu-so';
        }

        try {
            $this->notificationService->notify([
                'recipient_type' => Notification::RECIPIENT_USER,
                'recipient_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'severity' => $severity,
                'entity_type' => DigitalDocumentSubmission::class,
                'entity_id' => (int) $submission->id,
                'action_url' => $actionUrl,
                'dedupe_key' => $this->notificationService->buildEntityDedupeKey(
                    $type,
                    Notification::RECIPIENT_USER,
                    $userId,
                    DigitalDocumentSubmission::class,
                    (int) $submission->id
                ),
                'meta' => [
                    'status' => $submission->status,
                    'review_note' => $submission->review_note,
                    'approved_book_id' => $submission->approved_book_id,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
