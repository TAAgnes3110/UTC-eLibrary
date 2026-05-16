<?php

namespace App\Enums;

enum ResourceType: string
{
    case TEXTBOOK = 'textbook';

    case REFERENCE = 'reference';

    case DIGITAL = 'digital';

    public function label(): string
    {
        return match ($this) {
            self::TEXTBOOK => 'Giáo trình',
            self::REFERENCE => 'Tham khảo',
            self::DIGITAL => 'Tài liệu số',
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
