<?php

namespace App\Enums;

enum RoleType: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ADMIN = 'ADMIN';
    case LIBRARIAN = 'LIBRARIAN';
    case MEMBER = 'MEMBER';
    case GUEST = 'GUEST';

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
    public static function getNames(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($it) => [
            $it->name => [
                'value' => $it->value,
                'label' => __('enums.RoleType.' . $it->name),
            ],
        ])->toArray();
    }

    public static function getRoleTypes(): array
    {
        return collect(self::cases())->map(fn ($it) => [
            'id' => $it->value,
            'text' => __('enums.RoleType.' . $it->name),
        ])->toArray();
    }
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    public static function staffRoles(): array
    {
        return [self::LIBRARIAN->value, self::ADMIN->value, self::SUPER_ADMIN->value];
    }
    public static function readerTypes(): array
    {
        return [self::MEMBER->value, self::GUEST->value];
    }
}
