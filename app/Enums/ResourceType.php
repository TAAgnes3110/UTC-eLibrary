<?php

namespace App\Enums;

enum ResourceType: string
{
    case TEXTBOOK = 'textbook';

    case REFERENCE = 'reference';

    case THESIS = 'thesis';

    case JOURNAL = 'journal';

    case DIGITAL = 'digital';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
