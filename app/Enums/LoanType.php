<?php

namespace App\Enums;

enum LoanType: string
{
    case HOME = 'home';

    case ONSITE = 'onsite';

    public function label(): string
    {
        return match ($this) {
            self::HOME => 'Mượn về nhà',
            self::ONSITE => 'Đọc tại chỗ',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
