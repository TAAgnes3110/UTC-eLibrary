<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use App\Models\Warehouse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class WarehouseExport
{
    public static function stream(?array $ids = null): StreamedResponse
    {
        $query = Warehouse::query()->with('parent:id,code,name');
        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $rows = $query
            ->orderBy('id')
            ->get()
            ->map(fn (Warehouse $w) => [$w->code, $w->name])
            ->all();

        return FileHelpers::downloadExcel($rows, 'FileKhoSach.xlsx', ['Mã', 'Tên']);
    }
}
