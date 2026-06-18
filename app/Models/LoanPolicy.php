<?php

namespace App\Models;

use App\Enums\RoleType;
use Illuminate\Database\Eloquent\Builder;

class LoanPolicy extends BaseModel
{
    /** Cột `params` JSON được gán trực tiếp qua Eloquent — không dùng arrParams/toParams(). */
    protected static bool $persistParamsToDatabase = false;

    protected $fillable = [
        'code',
        'name',
        'user_type',
        'max_books',
        'max_days',
        'max_renewals',
        'overdue_fine_per_day',
        'allow_home',
        'allow_onsite',
        'params',
    ];

    protected $casts = [
        'params' => 'array',
        'max_books' => 'integer',
        'max_days' => 'integer',
        'max_renewals' => 'integer',
        'overdue_fine_per_day' => 'decimal:2',
        'allow_home' => 'boolean',
        'allow_onsite' => 'boolean',
    ];

    /**
     * Thứ tự hiển thị công khai: sinh viên → giảng viên → độc giả ngoài → còn lại.
     *
     * @param  Builder<static>  $query
     */
    public function scopeOrderedForReader(Builder $query): Builder
    {
        return $query
            ->orderByRaw("CASE COALESCE(user_type, '') WHEN 'STUDENT' THEN 1 WHEN 'TEACHER' THEN 2 WHEN 'MEMBER' THEN 3 ELSE 9 END")
            ->orderBy('id');
    }

    /**
     * Policy áp dụng khi user_type khớp enum RoleType, hoặc cột null = mặc định cho mọi đối tượng.
     */
    public function appliesToUser(User $user): bool
    {
        if ($this->user_type === null || $this->user_type === '') {
            return true;
        }

        return strcasecmp((string) $this->user_type, $user->user_type->value) === 0;
    }

    /**
     * Chính sách theo {@see RoleType} (một dòng / user_type — seed 3 nhóm: STUDENT, TEACHER, MEMBER).
     */
    public static function forRoleTypeValue(?string $roleTypeValue): ?self
    {
        if ($roleTypeValue === null || $roleTypeValue === '') {
            return null;
        }

        return static::query()->where('user_type', $roleTypeValue)->first();
    }

    public static function forUser(User $user): ?self
    {
        $value = $user->user_type instanceof RoleType
            ? $user->user_type->value
            : (string) ($user->user_type ?? '');

        return static::forRoleTypeValue($value);
    }

    /**
     * Ánh xạ holder thẻ thư viện → policy: sinh viên / giảng viên → STUDENT|TEACHER; bạn đọc (ngoài) → MEMBER.
     */
    public static function forLibraryHolderType(?string $holderType): ?self
    {
        return match ($holderType) {
            LibraryCard::HOLDER_TYPE_STUDENT => static::forRoleTypeValue(RoleType::STUDENT->value),
            LibraryCard::HOLDER_TYPE_TEACHER => static::forRoleTypeValue(RoleType::TEACHER->value),
            LibraryCard::HOLDER_TYPE_EXTERNAL => static::forRoleTypeValue(RoleType::MEMBER->value),
            default => null,
        };
    }
}
