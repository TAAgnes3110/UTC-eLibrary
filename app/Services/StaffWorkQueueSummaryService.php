<?php

namespace App\Services;

use App\Enums\RoleType;
use App\Models\LibraryCard;
use App\Models\LoanRenewalRequest;
use App\Models\User;
use App\Models\UserProfileUpdateRequest;

/**
 * Số lượng việc chờ xử lý cho thủ thư / quản trị (màn hình đăng nhập & nhắc việc).
 */
class StaffWorkQueueSummaryService
{
    /**
     * @return array{
     *     library_cards_pending_review:int,
     *     library_cards_pending_payment:int,
     *     user_profile_update_requests_pending:int,
     *     loan_renewal_requests_pending:int
     * }|null null nếu user không phải staff
     */
    public function summaryForUser(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }
        $roleValue = $user->user_type instanceof RoleType ? $user->user_type->value : (string) ($user->user_type ?? '');
        if ($roleValue === '' || ! in_array($roleValue, RoleType::staffRoles(), true)) {
            return null;
        }

        return $this->counts();
    }

    /**
     * @return array{
     *     library_cards_pending_review:int,
     *     library_cards_pending_payment:int,
     *     user_profile_update_requests_pending:int,
     *     loan_renewal_requests_pending:int
     * }
     */
    public function counts(): array
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
        ];
    }
}
