<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use App\Models\Classification;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ClassificationExport
{
    public static function stream(?array $ids = null): StreamedResponse
    {
        $query = Classification::query()->withCount('details');
        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $rows = $query
            ->orderBy('code')
            ->get()
            ->map(static fn (Classification $classification) => [
                $classification->code,
                $classification->name,
                (int) $classification->details_count,
            ])
            ->all();

        return FileHelpers::downloadExcel($rows, 'Danh_muc_phan_loai.xlsx', ['Mã phân loại', 'Tên phân loại', 'Số phân loại chi tiết']);
    }
}

