<?php

namespace App\Services\LibraryCard;

use App\Enums\LibraryCardStatus;
use App\Helpers\Helpers;
use App\Helpers\StudentTeacherRegistrationHelper;
use App\Http\Controllers\Api\LibraryCardController;
use App\Http\Requests\LibraryCardRequest;
use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LibraryCardGuestService
{
    public function __construct(
        private LibraryCardManagementService $management
    ) {}

    /**
     * Đăng ký thẻ không đăng nhập (guest) hoặc thủ thư tạo qua {@see LibraryCardController::store} với tùy chọn `user_id`.
     *
     * HTTP: trường bắt tuỳ `holder_type` trong {@see LibraryCardRequest::storeRules()}.
     * Có `user_id` + SV/GV: mặc định coi đã thu phí tại quầy → {@see LibraryCard::WORKFLOW_PENDING_PICKUP} (trừ khi gửi `paid_at_counter`: false).
     */
    public function create(array $data): LibraryCard
    {
        return DB::transaction(function () use ($data) {
            $holderType = $this->normalizeGuestHolderType($data);
            $linkedUser = $this->resolveLinkedUserForStaff($data);
            $paidAtCounter = $this->resolvePaidAtCounter($data, $holderType, $linkedUser !== null);
            $payload = $this->buildPayloadForGuest($holderType, $data, $linkedUser, $paidAtCounter);
            if ($linkedUser !== null) {
                $this->management->syncLinkedUserFromStaffCounterIssue($linkedUser, $holderType, $data);
            }
            $card = LibraryCard::query()->create($payload);

            if ($holderType === LibraryCard::HOLDER_TYPE_EXTERNAL) {
                $this->management->recordWalkInPayment($card, $data);
            } elseif ($paidAtCounter && in_array($holderType, [
                LibraryCard::HOLDER_TYPE_STUDENT,
                LibraryCard::HOLDER_TYPE_TEACHER,
            ], true)) {
                $this->management->recordWalkInPayment($card, $data);
            }

            return $card->fresh(['payment']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveLinkedUserForStaff(array $data): ?User
    {
        if (empty($data['user_id'])) {
            return null;
        }
        $user = User::query()->find((int) $data['user_id']);
        if ($user === null) {
            throw ValidationException::withMessages([
                'user_id' => [__('Không tìm thấy người dùng.')],
            ]);
        }
        $this->management->assertUserEligibleForStaffIssuedCard($user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolvePaidAtCounter(array $data, string $holderType, bool $hasLinkedUser): bool
    {
        if (array_key_exists('paid_at_counter', $data)) {
            return filter_var($data['paid_at_counter'], FILTER_VALIDATE_BOOLEAN);
        }
        if ($hasLinkedUser && in_array($holderType, [
            LibraryCard::HOLDER_TYPE_STUDENT,
            LibraryCard::HOLDER_TYPE_TEACHER,
        ], true)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildPayloadForGuest(string $holderType, array $data, ?User $linkedUser, bool $paidAtCounter): array
    {
        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '' && $linkedUser !== null && Helpers::filled($linkedUser->code)) {
            $code = trim((string) $linkedUser->code);
        }
        if ($code === '') {
            throw ValidationException::withMessages([
                'code' => [__('Mã định danh không được để trống.')],
            ]);
        }

        $payload = [
            'user_id' => $linkedUser?->id,
            'full_name' => trim((string) $data['full_name']),
            'email' => trim((string) $data['email']),
            'phone' => trim((string) $data['phone']),
            'address' => trim((string) $data['address']),
            'date_of_birth' => $data['date_of_birth'],
            'photo_path' => trim((string) $data['photo_path']),
            'holder_type' => $holderType,
            'code' => $code,
            'card_number' => $code,
            'status' => LibraryCardStatus::PENDING,
        ];

        if ($holderType === LibraryCard::HOLDER_TYPE_STUDENT) {
            $payload = array_merge($payload, $this->management->studentAffiliationPayload($data));
            if ($paidAtCounter) {
                $payload = $this->management->applyPaidAtCounterPendingPickup($payload);
            } else {
                $payload['workflow_status'] = LibraryCard::WORKFLOW_PENDING_REVIEW;
            }
        } elseif ($holderType === LibraryCard::HOLDER_TYPE_TEACHER) {
            $payload = array_merge($payload, $this->management->teacherAffiliationPayload($data));
            if ($paidAtCounter) {
                $payload = $this->management->applyPaidAtCounterPendingPickup($payload);
            } else {
                $payload['workflow_status'] = LibraryCard::WORKFLOW_PENDING_REVIEW;
            }
        } else {
            $today = now()->startOfDay();
            $payload['workflow_status'] = LibraryCard::WORKFLOW_ACTIVE;
            $payload['status'] = LibraryCardStatus::ACTIVE;
            $payload['issue_date'] = $today->toDateString();
            $payload['expiry_date'] = $today->copy()->addYear()->toDateString();
            if (Helpers::filled($data['external_organization'] ?? null)) {
                $payload['external_organization'] = trim((string) $data['external_organization']);
            }
        }

        $departmentId = StudentTeacherRegistrationHelper::optionalDepartmentId($data);
        if ($departmentId !== null) {
            $payload['department_id'] = $departmentId;
        }

        $registrationSource = $linkedUser !== null ? 'staff_counter' : 'guest';
        $payload['params'] = $this->management->attachRegistrationMetadata(
            $payload,
            $data,
            source: $registrationSource,
            actorLabel: $holderType,
            includeSchoolFields: $holderType !== LibraryCard::HOLDER_TYPE_EXTERNAL
        );

        if ($paidAtCounter && in_array($holderType, [
            LibraryCard::HOLDER_TYPE_STUDENT,
            LibraryCard::HOLDER_TYPE_TEACHER,
        ], true)) {
            $params = $payload['params'] ?? [];
            $params['counter_registration'] = array_merge($params['counter_registration'] ?? [], [
                'paid_at_counter' => true,
                'registered_at' => now()->toIso8601String(),
                'linked_user_id' => $linkedUser?->id,
            ]);
            $payload['params'] = $params;
        }

        return $payload;
    }

    /** @param  array<string, mixed>  $data */
    private function normalizeGuestHolderType(array $data): string
    {
        $holderType = isset($data['holder_type']) ? (string) $data['holder_type'] : '';
        if (! in_array($holderType, [
            LibraryCard::HOLDER_TYPE_STUDENT,
            LibraryCard::HOLDER_TYPE_TEACHER,
            LibraryCard::HOLDER_TYPE_EXTERNAL,
        ], true)) {
            throw ValidationException::withMessages([
                'holder_type' => [__('Loại thẻ không hợp lệ.')],
            ]);
        }

        return $holderType;
    }
}
