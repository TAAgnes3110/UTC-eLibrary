<?php

namespace App\Models;

use App\Enums\RoleType;

class LoanPolicy extends BaseModel
{
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
     * Ánh xạ loại thẻ thư viện → policy: thẻ đa năng = student|teacher; độc giả ngoài = MEMBER.
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
