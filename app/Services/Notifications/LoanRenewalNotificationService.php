<?php

namespace App\Services\Notifications;

use App\Enums\NotificationSeverity;
use App\Enums\NotificationType;
use App\Models\LoanRenewalRequest;
use App\Models\Notification;

/**
 * Thông báo yêu cầu gia hạn phiếu mượn (staff + bạn đọc).
 */
class LoanRenewalNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function notifyStaffRenewalSubmitted(LoanRenewalRequest $record): void
    {
        // Chỉ dùng thông báo tổng hợp theo số lượng cho staff (sync tại StaffWorkQueueNotificationService (được AuthController gọi)).
    }

    public function notifyRequesterRenewalResult(LoanRenewalRequest $record, bool $approved): void
    {
        $userId = (int) ($record->requested_by ?? 0);
        if ($userId <= 0) {
            return;
        }

        $loanCode = (string) ($record->loan?->loan_code ?? '#'.$record->loan_id);
        $type = $approved ? NotificationType::USER_LOAN_RENEWAL_APPROVED : NotificationType::USER_LOAN_RENEWAL_REJECTED;
        $title = $approved ? 'Gia hạn phiếu mượn được duyệt' : 'Gia hạn phiếu mượn bị từ chối';
        $message = $approved
            ? sprintf('Phiếu %s đã được gia hạn. Hạn trả mới: %s.', $loanCode, $record->requested_due_date?->toDateString() ?? '—')
            : sprintf('Yêu cầu gia hạn phiếu %s đã bị từ chối.', $loanCode);
        $severity = $approved ? NotificationSeverity::INFO : NotificationSeverity::WARNING;

        try {
            $this->notificationService->notify([
                'recipient_type' => Notification::RECIPIENT_USER,
                'recipient_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'severity' => $severity,
                'entity_type' => LoanRenewalRequest::class,
                'entity_id' => (int) $record->id,
                'action_url' => '/dich-vu/phieu-muon/'.$record->loan_id,
                'dedupe_key' => $this->notificationService->buildEntityDedupeKey(
                    $type,
                    Notification::RECIPIENT_USER,
                    $userId,
                    LoanRenewalRequest::class,
                    (int) $record->id
                ),
                'meta' => [
                    'approved' => $approved,
                    'review_note' => $record->review_note,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
