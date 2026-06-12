<?php

namespace App\Services\Notifications;

use App\Enums\NotificationSeverity;
use App\Enums\NotificationType;
use App\Enums\RoleType;
use App\Models\DigitalDocumentSubmission;
use App\Models\LibraryCard;
use App\Models\LoanRenewalRequest;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserProfileUpdateRequest;

/**
 * Thông báo tổng hợp theo hàng chờ xử lý của staff (chờ duyệt hồ sơ, gia hạn mượn, đồ án/luận văn chờ duyệt…).
 */
class StaffWorkQueueNotificationService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Đồng bộ digest hàng chờ cho toàn bộ tài khoản staff đang hoạt động (gọi khi có sự kiện mới: yêu cầu cập nhật hồ sơ, cấp thẻ…).
     *
     * Số liệu hàng chờ là toàn hệ thống (giống nhau cho mọi staff) nên chỉ COUNT một lần, tránh N×4 truy vấn khi có nhiều tài khoản thủ thư.
     */
    public function syncForAllActiveStaff(): void
    {
        try {
            $queue = $this->aggregateWorkQueueCounts();
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        User::query()
            ->whereIn('user_type', RoleType::staffRoles())
            ->where('is_active', true)
            ->select(['id', 'user_type'])
            ->chunkById(100, function ($users) use ($queue): void {
                foreach ($users as $user) {
                    try {
                        $this->syncForUser($user, $queue);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });
    }

    /**
     * Đồng bộ thông báo digest theo hàng chờ xử lý của staff.
     *
     * @param  array{
     *     library_cards_pending_review:int,
     *     library_cards_pending_payment:int,
     *     user_profile_update_requests_pending:int,
     *     loan_renewal_requests_pending:int,
     *     digital_document_submissions_pending:int
     * }|null  $precomputedQueue  Bỏ qua COUNT nếu truyền (dùng khi đã gọi {@see aggregateWorkQueueCounts()} cho nhiều staff).
     * @return array{
     *     library_cards_pending_review:int,
     *     library_cards_pending_payment:int,
     *     user_profile_update_requests_pending:int,
     *     loan_renewal_requests_pending:int,
     *     digital_document_submissions_pending:int
     * }|null
     */
    public function syncForUser(?User $user, ?array $precomputedQueue = null): ?array
    {
        if (! $user instanceof User || ! $this->isStaffUser($user)) {
            return null;
        }

        $queue = $precomputedQueue ?? $this->aggregateWorkQueueCounts();

        $recipientType = Notification::RECIPIENT_ADMIN;
        $recipientId = (int) $user->id;

        $this->upsertQueueDigestNotification(
            NotificationType::ADMIN_CARD_REQUEST_SUBMITTED,
            $recipientType,
            $recipientId,
            (int) ($queue['library_cards_pending_review'] ?? 0),
            'Yêu cầu cấp thẻ thư viện',
            'Hiện có %d hồ sơ cấp thẻ chờ duyệt.',
            '/admin/library-cards/requests',
            $queue
        );
        $this->upsertQueueDigestNotification(
            NotificationType::ADMIN_PROFILE_REVIEW_NEEDED,
            $recipientType,
            $recipientId,
            (int) ($queue['user_profile_update_requests_pending'] ?? 0),
            'Yêu cầu cập nhật hồ sơ',
            'Hiện có %d yêu cầu cập nhật hồ sơ chờ duyệt.',
            '/admin/users/update-requests',
            $queue
        );
        $this->upsertQueueDigestNotification(
            NotificationType::ADMIN_LOAN_RENEWAL_PENDING,
            $recipientType,
            $recipientId,
            (int) ($queue['loan_renewal_requests_pending'] ?? 0),
            'Yêu cầu gia hạn mượn',
            'Hiện có %d yêu cầu gia hạn mượn chờ duyệt.',
            '/admin/loans/renewal-requests',
            $queue
        );
        $this->upsertQueueDigestNotification(
            NotificationType::ADMIN_DIGITAL_DOCUMENT_SUBMISSION_PENDING,
            $recipientType,
            $recipientId,
            (int) ($queue['digital_document_submissions_pending'] ?? 0),
            'Đồ án, luận văn chờ duyệt',
            'Hiện có %d đồ án, luận văn chờ duyệt.',
            '/admin/books/digital/submissions',
            $queue
        );

        return $queue;
    }

    /**
     * @return array{
     *     library_cards_pending_review:int,
     *     library_cards_pending_payment:int,
     *     user_profile_update_requests_pending:int,
     *     loan_renewal_requests_pending:int,
     *     digital_document_submissions_pending:int
     * }|null
     */
    public function workQueueForUser(?User $user): ?array
    {
        if (! $user instanceof User || ! $this->isStaffUser($user)) {
            return null;
        }

        return $this->aggregateWorkQueueCounts();
    }

    /**
     * Số lượng chờ xử lý toàn hệ thống (dùng cho digest staff — không phụ thuộc từng user).
     *
     * @return array{
     *     library_cards_pending_review:int,
     *     library_cards_pending_payment:int,
     *     user_profile_update_requests_pending:int,
     *     loan_renewal_requests_pending:int,
     *     digital_document_submissions_pending:int
     * }
     */
    public function aggregateWorkQueueCounts(): array
    {
        return [
            'library_cards_pending_review' => (int) LibraryCard::query()
                ->where('workflow_status', LibraryCard::WORKFLOW_PENDING_REVIEW)
                ->count(),
            'library_cards_pending_payment' => (int) LibraryCard::query()
                ->where('workflow_status', LibraryCard::WORKFLOW_PENDING_PAYMENT)
                ->count(),
            'user_profile_update_requests_pending' => (int) UserProfileUpdateRequest::query()
                ->where('status', UserProfileUpdateRequest::STATUS_PENDING)
                ->count(),
            'loan_renewal_requests_pending' => (int) LoanRenewalRequest::query()
                ->where('status', LoanRenewalRequest::STATUS_PENDING)
                ->count(),
            'digital_document_submissions_pending' => (int) DigitalDocumentSubmission::query()
                ->where('status', DigitalDocumentSubmission::STATUS_PENDING)
                ->count(),
        ];
    }

    private function isStaffUser(User $user): bool
    {
        $roleValue = $user->user_type instanceof RoleType ? $user->user_type->value : (string) ($user->user_type ?? '');

        return $roleValue !== '' && in_array($roleValue, RoleType::staffRoles(), true);
    }

    /**
     * @param  array<string,int>  $queue
     */
    private function upsertQueueDigestNotification(
        NotificationType $type,
        string $recipientType,
        int $recipientId,
        int $count,
        string $title,
        string $messageFormat,
        string $actionUrl,
        array $queue
    ): void {
        $dedupeKey = implode(':', [$type->value, $recipientType, (string) $recipientId]);
        if ($count < 1) {
            Notification::query()
                ->where('dedupe_key', $dedupeKey)
                ->whereNull('read_at')
                ->delete();

            return;
        }

        $this->notificationService->notify([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'type' => $type->value,
            'title' => $title,
            'message' => sprintf($messageFormat, $count),
            'severity' => NotificationSeverity::INFO->value,
            'entity_type' => null,
            'entity_id' => null,
            'action_url' => $actionUrl,
            'meta' => [
                'count' => $count,
                'queue' => $queue,
            ],
            'dedupe_key' => $dedupeKey,
        ]);
    }
}
