<?php

declare(strict_types=1);

namespace App\Exports;

use App\Helpers\FileHelpers;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UserExport
{
    public static function stream(?array $ids = null): StreamedResponse
    {
        $headers = [
            'ID', 'Mã', 'Họ tên', 'Email', 'SĐT',
            'Vai trò', 'Trạng thái',
            'Mã khoa', 'Tên khoa', 'Tên bộ môn', 'Khóa',
            'Ngày sinh', 'Giới tính', 'Địa chỉ',
            'Số thẻ thư viện', 'Trạng thái thẻ', 'Ngày cấp thẻ', 'Ngày hết hạn thẻ',
            'Created by (id)', 'Created by (email)', 'Updated by (id)', 'Updated by (email)',
            'Deleted by (id)', 'Deleted by (email)', 'Created at', 'Updated at', 'Deleted at',
        ];

        $query = DB::table('users as u')
            ->leftJoin('faculties as f', 'f.id', '=', 'u.faculty_id')
            ->leftJoin('departments as d', 'd.id', '=', 'u.department_id')
            ->leftJoin('library_cards as lc', 'lc.user_id', '=', 'u.id')
            ->leftJoin('users as cb', 'cb.id', '=', 'u.created_by')
            ->leftJoin('users as ub', 'ub.id', '=', 'u.updated_by')
            ->leftJoin('users as dbu', 'dbu.id', '=', 'u.deleted_by')
            ->orderBy('u.id');

        if (! empty($ids)) {
            $query->whereIn('u.id', $ids);
        }

        $rows = $query->get([
            'u.id',
            'u.code',
            'u.name',
            'u.email',
            'u.phone',
            'u.user_type',
            'u.is_active',
            'u.date_of_birth',
            'u.gender',
            'u.address',
            'u.class_code',
            'u.cohort',
            'u.created_by',
            'u.updated_by',
            'u.deleted_by',
            'u.created_at',
            'u.updated_at',
            'u.deleted_at',
            'f.code as faculty_code',
            'f.name as faculty_name',
            'd.name as department_name',
            'lc.card_number as library_card_number',
            'lc.status as library_card_status',
            'lc.issue_date as library_card_issue_date',
            'lc.expiry_date as library_card_expiry_date',
            'cb.email as created_by_email',
            'ub.email as updated_by_email',
            'dbu.email as deleted_by_email',
        ])->map(static function ($u) {
            $role = (string) ($u->user_type ?? '');
            $status = ! empty($u->deleted_at)
                ? 'Ngưng hoạt động'
                : (((int) ($u->is_active ?? 0) === 1) ? 'Hoạt động' : 'Khóa');
            $cohortOrClassCode = (string) ($u->cohort ?? $u->class_code ?? '');
            return [
                $u->id, $u->code, $u->name, $u->email, $u->phone,
                $role, $status,
                $u->faculty_code, $u->faculty_name, $u->department_name, $cohortOrClassCode,
                $u->date_of_birth, $u->gender, $u->address,
                $u->library_card_number, $u->library_card_status,
                $u->library_card_issue_date,
                $u->library_card_expiry_date,
                $u->created_by, $u->created_by_email,
                $u->updated_by, $u->updated_by_email,
                $u->deleted_by, $u->deleted_by_email,
                $u->created_at,
                $u->updated_at,
                $u->deleted_at,
            ];
        })->all();

        return FileHelpers::downloadExcel($rows, 'FileNguoiDung.xlsx', $headers);
    }
}
