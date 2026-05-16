<?php

namespace App\Enums;

enum AccessMode: string
{
    case CirculationOnly = 'circulation_only';
    case OnlineOnly = 'online_only';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::CirculationOnly => 'Chỉ lưu thông tại thư viện',
            self::OnlineOnly => 'Chỉ trực tuyến',
            self::Both => 'Lưu thông + trực tuyến',
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
