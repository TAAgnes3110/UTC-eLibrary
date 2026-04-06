<?php

namespace App\Services;

use App\Enums\LibraryCardStatus;
use App\Models\LibraryCard;
use App\Models\User;
use App\Services\LibraryCard\LibraryCardAccountService;
use App\Services\LibraryCard\LibraryCardGuestService;
use App\Services\LibraryCard\LibraryCardManagementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

/**
 * Façade tương thích: logic tách {@see LibraryCardAccountService}, {@see LibraryCardGuestService}, {@see LibraryCardManagementService}.
 */
class LibraryCardService
{
    public const PAYMENT_DUE_DAYS = LibraryCardManagementService::PAYMENT_DUE_DAYS;

    public const PER_PAGE = LibraryCardManagementService::PER_PAGE;

    public function __construct(
        private LibraryCardAccountService $account,
        private LibraryCardGuestService $guest,
        private LibraryCardManagementService $management
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUserHaveAccount(User $user, array $data): LibraryCard
    {
        return $this->account->createForUserHaveAccount($user, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): LibraryCard
    {
        return $this->guest->create($data);
    }

    public function setPendingPaymentDeadline(LibraryCard $card): LibraryCard
    {
        return $this->management->setPendingPaymentDeadline($card);
    }

    public function approvePendingReviewAndActivate(LibraryCard $card, ?User $reviewer): LibraryCard
    {
        return $this->management->approvePendingReviewAndActivate($card, $reviewer);
    }

    public function rejectPendingReview(LibraryCard $card, ?string $notes, ?User $reviewer): LibraryCard
    {
        return $this->management->rejectPendingReview($card, $notes, $reviewer);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{holder_type: string, faculty_id: int, period_id: int, class_code: string}
     */
    public function studentAffiliationPayload(array $data): array
    {
        return $this->management->studentAffiliationPayload($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{holder_type: string, faculty_id: int}
     */
    public function teacherAffiliationPayload(array $data): array
    {
        return $this->management->teacherAffiliationPayload($data);
    }

    public function management(): LibraryCardManagementService
    {
        return $this->management;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateLibraryCard(LibraryCard $card, array $data): LibraryCard
    {
        return $this->management->updateLibraryCard($card, $data);
    }

    public function setWorkflowStatus(LibraryCard $card, string $workflowStatus): LibraryCard
    {
        return $this->management->setWorkflowStatus($card, $workflowStatus);
    }

    public function setCardStatus(LibraryCard $card, LibraryCardStatus $status): LibraryCard
    {
        return $this->management->setCardStatus($card, $status);
    }

    public function linkOrphanGuestCardToNewUser(User $user): ?LibraryCard
    {
        return $this->management->linkOrphanGuestCardToNewUser($user);
    }

    /**
     * @param  list<string>|null  $workflowStatuses
     * @param  list<string>|null  $keywordColumns
     */
    public function index(
        ?string $keyword,
        int $perPage = self::PER_PAGE,
        ?array $workflowStatuses = null,
        ?string $holderType = null,
        ?int $cardStatus = null,
        ?array $keywordColumns = null,
        bool $managementListOnly = false,
    ): LengthAwarePaginator {
        return $this->management->index($keyword, $perPage, $workflowStatuses, $holderType, $cardStatus, $keywordColumns, $managementListOnly);
    }

    public function updatePhoto(LibraryCard $card, UploadedFile $file): LibraryCard
    {
        return $this->management->updatePhoto($card, $file);
    }

    public function destroy(LibraryCard $card): void
    {
        $this->management->destroy($card);
    }

    public function trash(int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return $this->management->trash($perPage);
    }

    public function restore(int $id): ?LibraryCard
    {
        return $this->management->restore($id);
    }

    public function restoreMany(array $ids): int
    {
        return $this->management->restoreMany($ids);
    }

    public function forceDeleteTrashed(int $id): bool
    {
        return $this->management->forceDeleteTrashed($id);
    }

    public function forceDeleteManyTrashed(array $ids): int
    {
        return $this->management->forceDeleteManyTrashed($ids);
    }

    public function cancelLibraryCardApplication(LibraryCard $card, ?string $reason = null): LibraryCard
    {
        return $this->management->cancelLibraryCardApplication($card, $reason);
    }

    public function permanentlyDeleteLibraryCard(LibraryCard $card): void
    {
        $this->management->permanentlyDeleteLibraryCard($card);
    }
}
