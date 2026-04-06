<?php

namespace App\Services\LibraryCard;

use App\Enums\LibraryCardStatus;
use App\Enums\RoleType;
use App\Helpers\StudentTeacherRegistrationHelper;
use App\Http\Requests\MeLibraryCardStoreRequest;
use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Xin cấp / đăng ký thẻ khi người dùng đã có tài khoản và đăng nhập.
 *
 * HTTP: {@see MeLibraryCardStoreRequest} — khoa/niên khóa/lớp bắt buộc theo `user_type`.
 * Thanh toán tại quầy ngay (`paid_at_counter`): {@see LibraryCard::WORKFLOW_PENDING_PICKUP} + bản ghi thanh toán.
 */
class LibraryCardAccountService
{
    public function __construct(
        private LibraryCardManagementService $management
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUserHaveAccount(User $user, array $data): LibraryCard
    {
        return DB::transaction(function () use ($user, $data) {
            $paidAtCounter = filter_var($data['paid_at_counter'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $payload = $this->buildPayloadForAuthenticatedUser($user, $data, $paidAtCounter);
            $card = LibraryCard::query()->create($payload);
            if ($paidAtCounter) {
                $this->management->recordWalkInPayment($card, $data);
            }

            return $card->fresh(['payment']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildPayloadForAuthenticatedUser(User $user, array $data, bool $paidAtCounter = false): array
    {
        $this->ensureUserHasProfilePhoto($user);
        $role = $this->resolveReaderRoleOrFail($user);
        $identityCode = $this->resolveIdentityCodeForAccount($user, $data);

        $payload = [
            'user_id' => $user->id,
            'full_name' => $data['full_name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'address' => $data['address'] ?? $user->address,
            'date_of_birth' => $data['date_of_birth'] ?? $user->date_of_birth?->format('Y-m-d'),
            'photo_path' => $data['photo_path'] ?? $user->avatar,
            'workflow_status' => $paidAtCounter
                ? LibraryCard::WORKFLOW_PENDING_PICKUP
                : LibraryCard::WORKFLOW_PENDING_REVIEW,
            'status' => LibraryCardStatus::PENDING,
            'code' => $identityCode,
            'card_number' => $identityCode,
        ];

        $payload = array_merge($payload, $this->affiliationPayloadForAccountRole($role, $data));

        $departmentId = StudentTeacherRegistrationHelper::optionalDepartmentId($data);
        if ($departmentId !== null) {
            $payload['department_id'] = $departmentId;
        }

        $payload['params'] = $this->management->attachRegistrationMetadata(
            $payload,
            $data,
            source: $paidAtCounter ? 'user_account_counter' : 'user_account',
            actorLabel: $role->value,
            includeSchoolFields: in_array($role, [RoleType::STUDENT, RoleType::TEACHER], true)
        );

        if ($paidAtCounter) {
            $params = $payload['params'] ?? [];
            $params['counter_registration'] = array_merge($params['counter_registration'] ?? [], [
                'paid_at_counter' => true,
                'registered_at' => now()->toIso8601String(),
            ]);
            $payload['params'] = $params;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function affiliationPayloadForAccountRole(RoleType $role, array $data): array
    {
        return match ($role) {
            RoleType::STUDENT => $this->management->studentAffiliationPayload($data),
            RoleType::TEACHER => $this->management->teacherAffiliationPayload($data),
            default => ['holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL],
        };
    }

    private function ensureUserHasProfilePhoto(User $user): void
    {
        $avatar = $user->avatar;
        if ($avatar === null || $avatar === '' || (is_string($avatar) && trim($avatar) === '')) {
            throw ValidationException::withMessages([
                'avatar' => [__('Phải có ảnh đại diện (3×4) trên tài khoản.')],
            ]);
        }
    }

    private function resolveReaderRoleOrFail(User $user): RoleType
    {
        $role = $user->user_type;
        if (! $role instanceof RoleType) {
            throw ValidationException::withMessages([
                'user' => [__('Không xác định được loại tài khoản.')],
            ]);
        }

        if (in_array($role, [RoleType::SUPER_ADMIN, RoleType::ADMIN, RoleType::LIBRARIAN], true)) {
            throw ValidationException::withMessages([
                'user_type' => [__('Tài khoản nội bộ không dùng luồng đăng ký thẻ bạn đọc này.')],
            ]);
        }

        return $role;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveIdentityCodeForAccount(User $user, array $data): string
    {
        $code = trim((string) (($data['code'] ?? $user->code) ?? ''));
        if ($code === '') {
            throw ValidationException::withMessages([
                'code' => [__('Mã định danh không được để trống.')],
            ]);
        }

        return $code;
    }
}
