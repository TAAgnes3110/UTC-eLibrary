<?php

namespace App\Services\Notifications;

use App\Enums\NotificationSeverity;
use App\Enums\NotificationType;
use App\Enums\RoleType;
use App\Models\LoanRenewalRequest;
use App\Models\Notification;
use App\Models\User;

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
        $loan = $record->loan;
        $code = $loan?->loan_code ?? '#'.$record->loan_id;
        $requesterName = $record->relationLoaded('requester')
            ? (string) ($record->requester?->name ?? 'Bạn đọc')
            : 'Bạn đọc';

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
                    'type' => NotificationType::ADMIN_LOAN_RENEWAL_PENDING,
                    'title' => 'Có yêu cầu gia hạn phiếu mượn',
                    'message' => sprintf('%s gửi gia hạn cho phiếu %s.', $requesterName, $code),
                    'severity' => NotificationSeverity::INFO,
                    'entity_type' => LoanRenewalRequest::class,
                    'entity_id' => (int) $record->id,
                    'action_url' => '/admin/loans/renewal-requests',
                    'dedupe_key' => $this->notificationService->buildEntityDedupeKey(
                        NotificationType::ADMIN_LOAN_RENEWAL_PENDING,
                        Notification::RECIPIENT_ADMIN,
                        (int) $adminUser->id,
                        LoanRenewalRequest::class,
                        (int) $record->id
                    ),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }
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
