<?php

namespace App\Services\Notifications;

use App\Enums\NotificationSeverity;
use App\Enums\NotificationType;
use App\Enums\RoleType;
use App\Models\LibraryCard;
use App\Models\LoanRenewalRequest;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserProfileUpdateRequest;

class StaffWorkQueueNotificationService
{
    /**
     * Đồng bộ thông báo digest theo hàng chờ xử lý của staff.
     *
     * @return array{
     *     library_cards_pending_review:int,
     *     library_cards_pending_payment:int,
     *     user_profile_update_requests_pending:int,
     *     loan_renewal_requests_pending:int
     * }|null
     */
    public function syncForUser(?User $user): ?array
    {
        $queue = $this->workQueueForUser($user);
        if (! $user instanceof User || $queue === null) {
            return null;
        }

        $day = now()->toDateString();
        $recipientType = Notification::RECIPIENT_ADMIN;
        $recipientId = (int) $user->id;

        $this->upsertQueueDigestNotification(
            NotificationType::ADMIN_CARD_REQUEST_SUBMITTED,
            $recipientType,
            $recipientId,
            $day,
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
            $day,
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
            $day,
            (int) ($queue['loan_renewal_requests_pending'] ?? 0),
            'Yêu cầu gia hạn mượn',
            'Hiện có %d yêu cầu gia hạn mượn chờ duyệt.',
            '/admin/loans/renewal-requests',
            $queue
        );

        return $queue;
    }

    /**
     * @return array{
     *     library_cards_pending_review:int,
     *     library_cards_pending_payment:int,
     *     user_profile_update_requests_pending:int,
     *     loan_renewal_requests_pending:int
     * }|null
     */
    public function workQueueForUser(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }
        $roleValue = $user->user_type instanceof RoleType ? $user->user_type->value : (string) ($user->user_type ?? '');
        if ($roleValue === '' || ! in_array($roleValue, RoleType::staffRoles(), true)) {
            return null;
        }

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
        ];
    }

    /**
     * @param array<string,int> $queue
     */
    private function upsertQueueDigestNotification(
        NotificationType $type,
        string $recipientType,
        int $recipientId,
        string $day,
        int $count,
        string $title,
        string $messageFormat,
        string $actionUrl,
        array $queue
    ): void {
        $dedupeKey = implode(':', [$type->value, $recipientType, (string) $recipientId, $day]);
        if ($count < 1) {
            Notification::query()->where('dedupe_key', $dedupeKey)->delete();

            return;
        }

        Notification::query()->updateOrCreate(
            ['dedupe_key' => $dedupeKey],
            [
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
                    'digest_day' => $day,
                    'queue' => $queue,
                ],
                'read_at' => null,
            ]
        );
    }
}
