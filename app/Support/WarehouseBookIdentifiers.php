<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Book;
use App\Models\Warehouse;

/**
 * Sinh mã sách và số đăng ký cá biệt theo kho: mã kho + phần số, không dấu gạch nối.
 * Vẫn đọc được bản ghi cũ dạng KHO-GT-0001 / TVTT-0001.
 */
final class WarehouseBookIdentifiers
{
    public static function nextRegistrationNumber(Warehouse $warehouse): string
    {
        $whCode = trim((string) $warehouse->code);
        $last = Book::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereNotNull('registration_number')
            ->orderByDesc('id')
            ->value('registration_number');

        $next = self::parseNextRegistrationSequence($last, $whCode);

        return $whCode.sprintf('%04d', $next);
    }

    public static function nextBookCode(Warehouse $warehouse): string
    {
        $whCode = trim((string) $warehouse->code);
        $last = Book::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereNotNull('book_code')
            ->orderByDesc('id')
            ->value('book_code');

        $next = self::parseNextBookCodeSequence($last, $whCode);
        $orderPart = str_pad((string) $next, 4, '0', STR_PAD_LEFT);

        return $whCode.$orderPart;
    }

    private static function parseNextRegistrationSequence(?string $last, string $whCode): int
    {
        if ($last === null || $last === '') {
            return 1;
        }
        if ($whCode !== '' && str_starts_with($last, $whCode)) {
            $suffix = substr($last, strlen($whCode));
            if ($suffix !== '' && ctype_digit($suffix)) {
                return (int) $suffix + 1;
            }
        }
        if (preg_match('/-(\d+)$/', $last, $m)) {
            return (int) $m[1] + 1;
        }
        if (preg_match('/(\d+)$/', $last, $m)) {
            return (int) $m[1] + 1;
        }

        return 1;
    }

    private static function parseNextBookCodeSequence(?string $last, string $whCode): int
    {
        if ($last === null || $last === '') {
            return 1;
        }
        if (preg_match('/-(\d{4})$/', $last, $m)) {
            return (int) $m[1] + 1;
        }
        if ($whCode !== '' && str_starts_with($last, $whCode)) {
            $suffix = substr($last, strlen($whCode));
            if ($suffix !== '' && ctype_digit($suffix)) {
                return (int) $suffix + 1;
            }
        }

        return 1;
    }
}
