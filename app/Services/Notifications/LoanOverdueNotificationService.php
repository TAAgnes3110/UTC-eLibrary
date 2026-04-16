<?php

namespace App\Services\Notifications;

use App\Enums\NotificationSeverity;
use App\Enums\NotificationType;
use App\Enums\RoleType;
use App\Models\Loan;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

/**
 * Thông báo khi phiếu mượn chuyển sang quá hạn (bạn đọc: từng phiếu; staff: một dòng tổng hợp sau lệnh đồng bộ).
 */
class LoanOverdueNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Thông báo cho bạn đọc có tài khoản khi phiếu của họ vừa chuyển sang quá hạn.
     */
    public function notifyReaderForLoan(Loan $loan): void
    {
        $loan->loadMissing(['libraryCard:id,user_id,card_number,full_name']);

        $day = Carbon::today();

        $card = $loan->libraryCard;
        $readerId = (int) ($card?->user_id ?? 0);
        if ($readerId <= 0) {
            return;
        }

        try {
            $this->notificationService->notify([
                'recipient_type' => Notification::RECIPIENT_USER,
                'recipient_id' => $readerId,
                'type' => NotificationType::USER_LOAN_OVERDUE_REMINDER,
                'title' => 'Phiếu mượn đã quá hạn',
                'message' => sprintf(
                    'Phiếu %s đã quá hạn trả (hạn %s). Vui lòng mang tài liệu đến thư viện.',
                    (string) ($loan->loan_code ?? '#'.$loan->id),
                    $loan->due_date?->toDateString() ?? '—'
                ),
                'severity' => NotificationSeverity::WARNING,
                'entity_type' => Loan::class,
                'entity_id' => (int) $loan->id,
                'action_url' => '/dich-vu/phieu-muon/'.$loan->id,
                'dedupe_key' => $this->notificationService->buildOverdueDedupeKey(
                    NotificationType::USER_LOAN_OVERDUE_REMINDER,
                    Notification::RECIPIENT_USER,
                    $readerId,
                    Loan::class,
                    (int) $loan->id,
                    $day
                ),
                'meta' => [
                    'loan_code' => (string) ($loan->loan_code ?? ''),
                    'due_date' => $loan->due_date?->toDateString(),
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Một thông báo gộp cho mỗi staff: tổng số phiếu đang quá hạn (chưa trả), cập nhật theo ngày nếu chưa đọc.
     */
    public function notifyAdminsOverdueSummary(int $openOverdueLoanCount): void
    {
        if ($openOverdueLoanCount < 1) {
            return;
        }

        $day = Carbon::today()->toDateString();

        $adminUsers = User::query()
            ->whereIn('user_type', RoleType::staffRoles())
            ->where('is_active', true)
            ->select(['id'])
            ->get();

        foreach ($adminUsers as $adminUser) {
            $adminId = (int) $adminUser->id;
            try {
                $this->notificationService->notify([
                    'recipient_type' => Notification::RECIPIENT_ADMIN,
                    'recipient_id' => $adminId,
                    'type' => NotificationType::ADMIN_LOAN_OVERDUE_DETECTED,
                    'title' => 'Phiếu mượn quá hạn',
                    'message' => sprintf(
                        'Hiện có %d phiếu mượn đang quá hạn (chưa trả). Vui lòng vào Quản lý phiếu mượn để xử lý.',
                        $openOverdueLoanCount
                    ),
                    'severity' => NotificationSeverity::WARNING,
                    'entity_type' => null,
                    'entity_id' => null,
                    'action_url' => '/admin/loans',
                    'dedupe_key' => implode(':', [
                        NotificationType::ADMIN_LOAN_OVERDUE_DETECTED->value,
                        'digest',
                        Notification::RECIPIENT_ADMIN,
                        (string) $adminId,
                        $day,
                    ]),
                    'meta' => [
                        'overdue_open_count' => $openOverdueLoanCount,
                        'digest_day' => $day,
                    ],
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
