<?php

namespace App\Enums;

enum LibraryCardStatus: int
{
    case ACTIVE = 1;

    case EXPIRED = 2;

    case LOCKED = 3;

    case PENDING = 4;

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
