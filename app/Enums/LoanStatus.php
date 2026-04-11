<?php

namespace App\Enums;

enum LoanStatus: string
{
    /** Đã mượn (chưa trả). */
    case BORROWED = 'da_muon';

    case RETURNED = 'da_tra';

    case OVERDUE = 'qua_han';

    public function label(): string
    {
        return match ($this) {
            self::BORROWED => 'Đang mượn',
            self::RETURNED => 'Đã trả',
            self::OVERDUE => 'Quá hạn',
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
