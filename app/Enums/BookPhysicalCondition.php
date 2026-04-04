<?php

namespace App\Enums;

/**
 * Tình trạng vật lý bản in (khác với trạng thái lưu thông `status` trên book_copies).
 */
enum BookPhysicalCondition: string
{
    case GOOD = 'good';

    case FAIR = 'fair';

    case WORN = 'worn';

    case NEEDS_REPAIR = 'needs_repair';

    case DAMAGED = 'damaged';

    /**
     * Cho phép mượn (về nhà hoặc tại chỗ) theo mặc định UTC eLibrary — hỏng / chờ sửa thì không.
     */
    public function allowsBorrowing(): bool
    {
        return match ($this) {
            self::GOOD, self::FAIR, self::WORN => true,
            self::NEEDS_REPAIR, self::DAMAGED => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Giá trị DB được coi là cho mượn được (dùng trong query).
     *
     * @return list<string>
     */
    public static function borrowableValues(): array
    {
        return [
            self::GOOD->value,
            self::FAIR->value,
            self::WORN->value,
        ];
    }
}
