<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\LibraryCardStatus;
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

        $workflowLabels = [
            LibraryCard::WORKFLOW_PENDING_REVIEW => 'Chờ duyệt',
            LibraryCard::WORKFLOW_PENDING_PAYMENT => 'Chờ thanh toán',
            LibraryCard::WORKFLOW_PENDING_PICKUP => 'Chờ lấy thẻ',
            LibraryCard::WORKFLOW_ACTIVE => 'Đang hiệu lực',
            LibraryCard::WORKFLOW_REJECTED => 'Đã từ chối',
            LibraryCard::WORKFLOW_CANCELLED => 'Đã hủy',
            LibraryCard::WORKFLOW_DRAFT => 'Chờ duyệt',
            LibraryCard::WORKFLOW_EXPIRED => 'Đang hiệu lực (cũ)',
            LibraryCard::WORKFLOW_REVOKED => 'Đang hiệu lực (cũ)',
        ];

        $rows = $query->get()->map(function (LibraryCard $c) use ($workflowLabels) {
            $status = $c->status instanceof LibraryCardStatus
                ? $c->status
                : LibraryCardStatus::tryFrom((int) $c->status);
            $statusLabel = $status?->label() ?? (string) $c->status;
            $ws = LibraryCard::normalizeWorkflowStatus((string) $c->workflow_status);
            $wsLabel = $workflowLabels[$ws] ?? $ws;

            return [
                $c->id,
                $c->card_number,
                $c->code,
                $c->full_name,
                $c->email,
                $c->phone,
                $c->holder_type,
                $wsLabel,
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
