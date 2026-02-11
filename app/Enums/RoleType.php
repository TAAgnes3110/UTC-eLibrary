<?php

namespace App\Enums;

enum RoleType: string
{
    case SUPER_ADMIN = "SUPER_ADMIN";
    case ADMIN = "ADMIN";
    case LIBRARIAN = "LIBRARIAN";
    case MEMBER = "MEMBER";
    case GUEST = "GUEST";

    public static function getName(int|string $value): ?string
    {
        $result = collect(self::cases())->where('value', $value)->first();
        return $result ? __('enums.RoleType.' . $result->name) : null;
    }

    public static function getNames(): array
    {
        return collect(self::cases())->mapWithKeys(fn($it) => [
            $it->name => [
                'value' => $it->value,
                'label' => __('enums.RoleType.' . $it->name),
            ]
        ])->toArray();
    }

    public static function getRoleTypes(): array
    {
        return collect(self::cases())->map(fn($it) => [
            'id' => $it->value,
            'text' => __('enums.RoleType.' . $it->name),
        ])->toArray();
    }

    public static function values(): array
    {
        return collect(self::cases())->map(fn($it) => $it->value)->toArray();
    }

    public static function staffRoles(): array
    {
        return [self::LIBRARIAN->value, self::ADMIN->value, self::SUPER_ADMIN->value];
    }
}
