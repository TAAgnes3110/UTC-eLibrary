<?php

namespace App\Enums;

/**
 * Vai trò / loại người dùng (cột users.user_type).
 * Khớp với enum trong migration create_users_table.
 */
enum RoleType: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ADMIN = 'ADMIN';
    case LIBRARIAN = 'LIBRARIAN';
    case MEMBER = 'MEMBER';
    case GUEST = 'GUEST';

    /** Nhãn hiển thị (đa ngôn ngữ). */
    public function label(): string
    {
        return self::getName($this->value) ?? $this->value;
    }

    /**
     * Lấy nhãn theo giá trị (value).
     *
     * @param int|string $value
     * @return string|null
     */
    public static function getName(int|string $value): ?string
    {
        $result = collect(self::cases())->where('value', $value)->first();
        return $result ? __('enums.RoleType.' . $result->name) : null;
    }

    /** [ name => [ value, label ] ] cho form/options. */
    public static function getNames(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($it) => [
            $it->name => [
                'value' => $it->value,
                'label' => __('enums.RoleType.' . $it->name),
            ],
        ])->toArray();
    }

    /** [ [ id => value, text => label ], ... ] cho dropdown. */
    public static function getRoleTypes(): array
    {
        return collect(self::cases())->map(fn ($it) => [
            'id' => $it->value,
            'text' => __('enums.RoleType.' . $it->name),
        ])->toArray();
    }

    /** Mảng giá trị value (cho validation Rule::in). */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Vai trò nhân viên (có quyền vào khu vực admin). */
    public static function staffRoles(): array
    {
        return [self::LIBRARIAN->value, self::ADMIN->value, self::SUPER_ADMIN->value];
    }

    /** Vai trò bạn đọc (độc giả). */
    public static function readerTypes(): array
    {
        return [self::MEMBER->value, self::GUEST->value];
    }
}
