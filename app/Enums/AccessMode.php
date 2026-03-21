<?php

namespace App\Enums;

enum AccessMode: string
{
    case CirculationOnly = 'circulation_only';
    case OnlineOnly = 'online_only';
    case Both = 'both';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
