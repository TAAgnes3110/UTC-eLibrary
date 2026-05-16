<?php

namespace App\Enums;

enum BookStatus: int
{
    case AVAILABLE = 1;

    case BORROWED = 2;

    case PROCESSING = 3;

    case LOST = 4;

    case MAINTENANCE = 5;

    case DISCARDED = 6;

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Sẵn sàng',
            self::BORROWED => 'Đang mượn',
            self::PROCESSING => 'Đang xử lý',
            self::LOST => 'Báo mất',
            self::MAINTENANCE => 'Bảo trì',
            self::DISCARDED => 'Thanh lý',
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
