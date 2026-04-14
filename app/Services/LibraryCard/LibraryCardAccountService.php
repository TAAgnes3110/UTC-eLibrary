<?php

namespace App\Services\LibraryCard;

use App\Enums\LibraryCardStatus;
use App\Enums\RoleType;
use App\Helpers\FileHelpers;
use App\Helpers\StudentTeacherRegistrationHelper;
use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LibraryCardAccountService
{
    public function __construct(
        private LibraryCardManagementService $management
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForUserHaveAccount(User $user, array $data, ?UploadedFile $photoFile = null): LibraryCard
    {
        return DB::transaction(function () use ($user, $data, $photoFile) {
            $paidAtCounter = filter_var($data['paid_at_counter'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $payload = $this->buildPayloadForAuthenticatedUser($user, $data, $paidAtCounter, $photoFile);
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
    private function buildPayloadForAuthenticatedUser(User $user, array $data, bool $paidAtCounter = false, ?UploadedFile $photoFile = null): array
    {
        $role = $this->resolveReaderRoleOrFail($user);
        $identityCode = $this->resolveIdentityCodeForAccount($user);
        $photoPath = $this->resolvePhotoPathForRegistration($user, $data, $photoFile);

        $payload = [
            'user_id' => $user->id,
            'full_name' => $data['full_name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'address' => $data['address'] ?? $user->address,
            'date_of_birth' => $data['date_of_birth'] ?? $user->date_of_birth?->format('Y-m-d'),
            'photo_path' => $photoPath,
            'workflow_status' => $paidAtCounter
                ? LibraryCard::WORKFLOW_PENDING_PICKUP
                : LibraryCard::WORKFLOW_PENDING_REVIEW,
            'status' => LibraryCardStatus::PENDING,
            'code' => $identityCode,
            'card_number' => $identityCode,
        ];

        $payload = array_merge($payload, $this->affiliationPayloadForAccountRole($role, $user));

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
     * @return array<string, mixed>
     */
    private function affiliationPayloadForAccountRole(RoleType $role, User $user): array
    {
        $accountData = [
            'faculty_id' => $user->faculty_id,
            'period_id' => $user->period_id,
            'class_code' => $user->class_code,
        ];

        return match ($role) {
            RoleType::STUDENT => $this->management->studentAffiliationPayload($accountData),
            RoleType::TEACHER => $this->management->teacherAffiliationPayload($accountData),
            default => ['holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL],
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolvePhotoPathForRegistration(User $user, array $data, ?UploadedFile $photoFile = null): string
    {
        if ($photoFile !== null) {
            return FileHelpers::storeUploadedFile($photoFile, 'public', 'upload/library-cards/photos');
        }

        $photoPath = trim((string) ($data['photo_path'] ?? ''));
        if ($photoPath !== '') {
            return $photoPath;
        }

        $avatar = trim((string) ($user->avatar ?? ''));
        if ($avatar !== '') {
            return $avatar;
        }

        throw ValidationException::withMessages([
            'avatar' => [__('Phải có ảnh đại diện (3×4). Bạn có thể tải trực tiếp tại trang cấp thẻ hoặc cập nhật trong tài khoản.')],
        ]);
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
     */
    private function resolveIdentityCodeForAccount(User $user): string
    {
        $code = trim((string) ($user->code ?? ''));
        if ($code === '') {
            throw ValidationException::withMessages([
                'code' => [__('Mã định danh còn thiếu trên tài khoản. Vui lòng cập nhật tài khoản trước khi cấp thẻ.')],
            ]);
        }

        return $code;
    }
}
