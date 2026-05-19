<?php

namespace App\Services\Notifications;

use App\Enums\LoanStatus;
use App\Enums\NotificationSeverity;
use App\Enums\NotificationType;
use App\Enums\RoleType;
use App\Models\Loan;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Nhắc phiếu đang mượn sắp đến hạn trả (N ngày trước hạn — cấu hình LOAN_DUE_SOON_DAYS_BEFORE).
 */
class LoanDueSoonNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * @return int số phiếu đã gửi nhắc (mỗi phiếu tối đa một thông báo chưa đọc / ngày theo dedupe).
     */
    public function notifyForLoansDueSoon(CarbonInterface $today, ?int $daysBefore = null): int
    {
        $days = $daysBefore ?? (int) config('notifications.loan_due_soon_days_before', 2);
        $days = max(1, min(14, $days));

        $dueOn = $today instanceof Carbon ? $today->copy() : Carbon::parse($today);
        $dueOn = $dueOn->addDays($days)->toDateString();

        $loans = Loan::query()
            ->where('status', LoanStatus::BORROWED)
            ->whereNull('return_date')
            ->whereDate('due_date', $dueOn)
            ->with(['libraryCard:id,user_id,card_number,full_name'])
            ->get();

        $digestDay = $today instanceof Carbon ? $today->copy()->startOfDay() : Carbon::parse($today)->startOfDay();
        foreach ($loans as $loan) {
            $this->notifyReaderForLoan($loan, $digestDay, $days);
        }

        $count = $loans->count();
        $this->notifyAdminsDueSoonSummary($count, (string) $dueOn, $digestDay, $days);

        return $count;
    }

    /**
     * @deprecated Dùng notifyForLoansDueSoon()
     */
    public function notifyForLoansDueInTwoDays(CarbonInterface $today): int
    {
        return $this->notifyForLoansDueSoon($today);
    }

    private function notifyReaderForLoan(Loan $loan, CarbonInterface $day, int $daysBefore): void
    {
        $card = $loan->libraryCard;
        $readerId = (int) ($card?->user_id ?? 0);
        if ($readerId <= 0) {
            return;
        }

        try {
            $dueStr = $loan->due_date?->toDateString() ?? '—';
            $this->notificationService->notify([
                'recipient_type' => Notification::RECIPIENT_USER,
                'recipient_id' => $readerId,
                'type' => NotificationType::USER_LOAN_DUE_SOON_REMINDER,
                'title' => 'Phiếu mượn sắp đến hạn trả',
                'message' => sprintf(
                    'Phiếu %s sẽ đến hạn trả sau %s (hạn %s). Vui lòng mang tài liệu đến thư viện trước hoặc đúng ngày hẹn trả.',
                    (string) ($loan->loan_code ?? '#'.$loan->id),
                    $this->formatDaysAheadLabel($daysBefore),
                    $dueStr
                ),
                'severity' => NotificationSeverity::INFO,
                'entity_type' => Loan::class,
                'entity_id' => (int) $loan->id,
                'action_url' => '/dich-vu/phieu-muon/'.$loan->id,
                'dedupe_key' => $this->notificationService->buildOverdueDedupeKey(
                    NotificationType::USER_LOAN_DUE_SOON_REMINDER,
                    Notification::RECIPIENT_USER,
                    $readerId,
                    Loan::class,
                    (int) $loan->id,
                    $day
                ),
                'meta' => [
                    'loan_code' => (string) ($loan->loan_code ?? ''),
                    'due_date' => $dueStr,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function notifyAdminsDueSoonSummary(int $dueSoonCount, string $dueDate, CarbonInterface $day, int $daysBefore): void
    {
        if ($dueSoonCount < 1) {
            return;
        }

        $dayStr = $day instanceof Carbon ? $day->toDateString() : Carbon::parse($day)->toDateString();

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
                    'type' => NotificationType::ADMIN_LOAN_DUE_SOON_DIGEST,
                    'title' => 'Phiếu mượn sắp đến hạn',
                    'message' => sprintf(
                        'Có %d phiếu đang mượn sẽ đến hạn trả sau %s (ngày hẹn trả %s). Vui lòng vào Quản lý phiếu mượn để theo dõi.',
                        $dueSoonCount,
                        $this->formatDaysAheadLabel($daysBefore),
                        $dueDate
                    ),
                    'severity' => NotificationSeverity::INFO,
                    'entity_type' => null,
                    'entity_id' => null,
                    'action_url' => '/admin/loans',
                    'dedupe_key' => implode(':', [
                        NotificationType::ADMIN_LOAN_DUE_SOON_DIGEST->value,
                        'digest',
                        Notification::RECIPIENT_ADMIN,
                        (string) $adminId,
                        $dayStr,
                    ]),
                    'meta' => [
                        'due_soon_open_count' => $dueSoonCount,
                        'due_date' => $dueDate,
                        'digest_day' => $dayStr,
                    ],
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function formatDaysAheadLabel(int $days): string
    {
        return $days === 1 ? '1 ngày' : "{$days} ngày";
    }
}
