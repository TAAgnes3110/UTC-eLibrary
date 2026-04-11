<?php

namespace App\Enums;

enum LoanItemCondition: string
{
    case GOOD = 'tot';
    case DAMAGED = 'hong';
    case LOST = 'mat';

    public function label(): string
    {
        return match ($this) {
            self::GOOD => 'Sách còn tốt',
            self::DAMAGED => 'Sách hư hỏng',
            self::LOST => 'Sách bị mất',
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
