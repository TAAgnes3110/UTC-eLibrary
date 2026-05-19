<?php

namespace App\Support;

use App\Rules\DateOfBirth;

final class DateOfBirthRules
{
    /** @return list<string|DateOfBirth> */
    public static function required(): array
    {
        return ['required', 'date', new DateOfBirth];
    }

    /** @return list<string|DateOfBirth> */
    public static function nullable(): array
    {
        return ['nullable', 'date', new DateOfBirth];
    }

    /** @return list<string|DateOfBirth> */
    public static function sometimesNullable(): array
    {
        return ['sometimes', 'nullable', 'date', new DateOfBirth];
    }

    /** @return list<string|DateOfBirth> */
    public static function sometimes(): array
    {
        return ['sometimes', 'date', new DateOfBirth];
    }
}
