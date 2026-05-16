<?php

namespace App\Enums;

enum LibraryCardStatus: int
{
    case ACTIVE = 1;

    case EXPIRED = 2;

    case LOCKED = 3;

    case PENDING = 4;

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Đang hiệu lực',
            self::EXPIRED => 'Hết hạn',
            self::LOCKED => 'Khóa',
            self::PENDING => 'Chờ duyệt',
        };
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
