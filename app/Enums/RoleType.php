<?php

namespace App\Enums;

enum RoleType: string
{
    case SUPER_ADMIN = "SUPER_ADMIN";
    case ADMIN = "ADMIN";
    case LIBRARIAN = "LIBRARIAN";
    case TEACHER = "TEACHER";
    case STUDENT = "STUDENT";
    case GUEST = "GUEST";


    /**
     * @param int|string $value
     *
     * @return array|string|null
     */
    public static function getName(int|string $value): array|string|null
    {
        $result = collect(self::cases())->where('value', $value)->first();
        if (!$result) {
            return null;
        }
        return __('enums.RoleType.' . $result->name);
    }

    /**
     *
     * @return array|string|null
     */
    public static function getNames(): array|string|null
    {
        return collect(self::cases())->mapWithKeys(function ($it) {
            return [$it->name => [
                'value' => $it->value,
                'label' => __('enums.RoleType.' . $it->name),
            ]];
        })->toArray();
    }
    public static function getRoleTypes(): array|string|null
    {
        return collect(self::cases())->map(function ($it) {
            return [
                'id' => $it->value,
                'text' => __('enums.RoleType.' . $it->name),
            ];
        })->toArray();
    }
    public static function values(): array
    {
        return collect(self::cases())->map(function ($it) {
            return $it->value;
        })->toArray();
    }

    /**
     *
     * @return string
     */
    public static function getComment(): string
    {
        return collect(self::cases())->map(function ($it) {
            return $it->value . ':' . __('enums.RoleType.' . $it->name);
        })->join(', ');
    }
}
