<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use App\Models\LibraryCard;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LibraryCardExport
{
    /**
     * @param  list<int|string>|null  $ids
     */
    public static function stream(?array $ids = null): StreamedResponse
    {
        $query = LibraryCard::query()
            ->with([
                'faculty:id,code,name',
                'period:id,code,name',
                'payment',
            ])
            ->orderByDesc('id');

        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $headers = [
            'ID', 'Mã thẻ', 'Mã định danh', 'Họ tên', 'Email', 'SĐT',
            'Loại thẻ', 'Trạng thái quy trình', 'Trạng thái thẻ',
            'Khoa', 'Niên khóa', 'Lớp',
            'TT thanh toán', 'Số tiền',
            'Ngày cấp', 'Ngày hết hạn',
            'Created at', 'Updated at',
        ];

        $rows = $query->get()->map(function (LibraryCard $c) {
            $status = $c->status instanceof \BackedEnum ? $c->status->value : $c->status;
            $statusLabel = match ((int) $status) {
                1 => 'active',
                2 => 'expired',
                3 => 'locked',
                4 => 'pending',
                default => (string) $status,
            };

            return [
                $c->id,
                $c->card_number,
                $c->code,
                $c->full_name,
                $c->email,
                $c->phone,
                $c->holder_type,
                $c->workflow_status,
                $statusLabel,
                $c->faculty?->name,
                $c->period?->name,
                $c->class_code,
                $c->payment?->payment_status,
                $c->payment?->payment_amount,
                $c->issue_date?->format('Y-m-d'),
                $c->expiry_date?->format('Y-m-d'),
                $c->created_at?->toIso8601String(),
                $c->updated_at?->toIso8601String(),
            ];
        })->all();

        return FileHelpers::downloadExcel($rows, 'DanhSachTheThuVien.xlsx', $headers);
    }
}
