<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UserExport
{
    public static function stream(?array $ids = null): StreamedResponse
    {
        $with = [
            'faculty:id,code,name',
            'department:id,name,faculty_id',
            'libraryCard',
            'createdBy:id,email,name',
            'updatedBy:id,email,name',
            'deletedBy:id,email,name',
        ];

        $cols = [
            'id', 'code', 'name', 'email', 'phone', 'user_type', 'is_active',
            'date_of_birth', 'gender', 'address', 'faculty_id', 'department_id', 'cohort',
            'created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at',
        ];

        $headers = [
            'ID', 'Mã', 'Họ tên', 'Email', 'SĐT',
            'Vai trò', 'Trạng thái (active/blocked/inactive)',
            'Mã khoa', 'Tên khoa', 'Tên bộ môn', 'Khóa',
            'Ngày sinh', 'Giới tính', 'Địa chỉ',
            'Số thẻ thư viện', 'Trạng thái thẻ', 'Ngày cấp thẻ', 'Ngày hết hạn thẻ',
            'Created by (id)', 'Created by (email)', 'Updated by (id)', 'Updated by (email)',
            'Deleted by (id)', 'Deleted by (email)', 'Created at', 'Updated at', 'Deleted at',
        ];

        $query = User::query()->with($with)->orderBy('id');
        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        }

        $rows = $query->get($cols)->map(function (User $u) {
            $typeLabel = null;
            if (is_object($u->user_type) && method_exists($u->user_type, 'label')) {
                $typeLabel = $u->user_type->label();
            }
            if ($typeLabel === null || $typeLabel === '') {
                $typeLabel = $u->user_type instanceof \UnitEnum
                    ? ($u->user_type->name ?? null)
                    : null;
            }
            if ($typeLabel === null || $typeLabel === '') {
                $typeLabel = ($u->user_type instanceof \BackedEnum)
                    ? (string) $u->user_type->value
                    : (string) ($u->getRawOriginal('user_type') ?? $u->user_type ?? '');
            }

            $status = $u->trashed() ? 'inactive' : ($u->is_active ? 'active' : 'blocked');

            return [
                $u->id, $u->code, $u->name, $u->email, $u->phone,
                $typeLabel, $status,
                $u->faculty?->code, $u->faculty?->name, $u->department?->name, $u->cohort,
                $u->date_of_birth?->format('Y-m-d'), $u->gender, $u->address,
                $u->libraryCard?->card_number, $u->libraryCard?->status,
                $u->libraryCard?->issue_date?->format('Y-m-d'),
                $u->libraryCard?->expiry_date?->format('Y-m-d'),
                $u->created_by, $u->createdBy?->email,
                $u->updated_by, $u->updatedBy?->email,
                $u->deleted_by, $u->deletedBy?->email,
                $u->created_at?->toIso8601String(),
                $u->updated_at?->toIso8601String(),
                $u->deleted_at?->toIso8601String(),
            ];
        })->all();

        return FileHelpers::downloadExcel($rows, 'FileNguoiDung.xlsx', $headers);
    }
}
