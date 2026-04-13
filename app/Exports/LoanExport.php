<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LoanExport
{
    /**
     * @param  Builder<Loan>  $query
     * @param  list<int>|null  $idOrder  Thứ tự dòng khi xuất theo lựa chọn (ổn định trên SQLite/MySQL).
     */
    public static function stream(Builder $query, ?array $idOrder = null): StreamedResponse
    {
        $headers = [
            'ID',
            'Mã phiếu',
            'Trạng thái',
            'Mã thẻ',
            'Họ tên độc giả',
            'Người tạo',
            'Ngày mượn',
            'Ngày hẹn trả',
            'Ngày trả',
            'Created at',
            'Updated at',
        ];

        $collection = $query
            ->with(['libraryCard:id,card_number,full_name', 'createdBy:id,name'])
            ->get();

        if ($idOrder !== null && $idOrder !== []) {
            $collection = $collection->sortBy(
                fn (Loan $l) => ($i = array_search((int) $l->id, $idOrder, true)) === false ? PHP_INT_MAX : $i
            )->values();
        }

        $rows = $collection
            ->map(function (Loan $l) {
                return [
                    $l->id,
                    $l->loan_code,
                    $l->status,
                    $l->libraryCard?->card_number,
                    $l->libraryCard?->full_name,
                    $l->createdBy?->name,
                    $l->loan_date?->format('Y-m-d'),
                    $l->due_date?->format('Y-m-d'),
                    $l->return_date?->format('Y-m-d'),
                    $l->created_at?->toIso8601String(),
                    $l->updated_at?->toIso8601String(),
                ];
            })
            ->all();

        return FileHelpers::downloadExcel($rows, 'DanhSachPhieuMuon.xlsx', $headers);
    }
}
