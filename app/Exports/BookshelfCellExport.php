<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use App\Models\BookshelfCell;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BookshelfCellExport
{
    public static function stream(?array $ids = null): StreamedResponse
    {
        $query = BookshelfCell::query()
            ->with([
                'warehouse:id,code,name',
                'classification:id,code,name',
                'classificationDetail:id,code,name',
            ]);

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $rows = $query
            ->orderBy('warehouse_id')
            ->orderBy('row_index')
            ->orderBy('column_index')
            ->get()
            ->map(static fn (BookshelfCell $cell) => [
                $cell->warehouse?->code ?? '',
                $cell->warehouse?->name ?? '',
                $cell->row_index,
                $cell->column_index,
                $cell->label,
                $cell->classification?->name ?? '',
                $cell->classificationDetail?->name ?? '',
                $cell->current_quantity ?? 0,
            ])
            ->all();

        return FileHelpers::downloadExcel($rows, 'KeSach.xlsx', [
            'Mã kho',
            'Tên kho',
            'Hàng',
            'Cột',
            'Nhãn',
            'Tên phân loại',
            'Tên phân loại chi tiết',
            'Số lượng hiện tại',
        ]);
    }
}
