<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Http\Request;
final class BulkZipRequestHelper
{
    /**
     * @return list<int>|null null = không lọc theo id (cập nhật mọi bản ghi có mã khớp trong zip)
     */
    public static function parseFilterIds(Request $request): ?array
    {
        if (!$request->has('ids')) {
            return null;
        }
        $v = $request->input('ids');
        if (is_array($v)) {
            $raw = $v;
        } elseif (is_string($v) && $v !== '') {
            $decoded = json_decode($v, true);
            if (!is_array($decoded)) {
                return null;
            }
            $raw = $decoded;
        } else {
            return null;
        }
        $ids = array_values(array_unique(array_filter(array_map(static fn ($x) => (int) $x, $raw), static fn (int $id) => $id > 0)));
        if ($ids === []) {
            return null;
        }

        return $ids;
    }
}
