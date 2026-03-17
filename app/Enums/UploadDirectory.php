<?php

namespace App\Enums;

class UploadDirectory
{
    public const BASE = 'upload';

    public static function forTable(string $table): string
    {
        return self::BASE . '/' . trim($table, '/');
    }
}

