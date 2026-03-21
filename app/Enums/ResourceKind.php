<?php

namespace App\Enums;

enum ResourceKind: string
{
    case Print = 'print';
    case Digital = 'digital';
    case Hybrid = 'hybrid';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
