<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersFileMauExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected ?array $userIds = null,
    ) {
    }

    public function collection(): Collection
    {
        $query = User::query()
            ->whereNull('deleted_at')
            ->with(['faculty:id,name,code', 'department:id,name,faculty_id']);

        if (!empty($this->userIds)) {
            $query->whereIn('id', $this->userIds);
        }

        return $query
            ->orderBy('id')
            ->get()
            ->map(function (User $user) {
                $statusLabel = $user->is_active ? 'Hoạt động' : 'Khóa';
                return [
                    $user->id,
                    $user->code,
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->user_type?->value ?? (string) $user->user_type,
                    $statusLabel,
                    optional($user->faculty)->name,
                    optional($user->department)->name,
                    $user->cohort,
                    optional($user->created_at)?->format('Y-m-d H:i:s'),
                    optional($user->updated_at)?->format('Y-m-d H:i:s'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'MaDinhDanh',
            'HoTen',
            'Email',
            'SoDienThoai',
            'LoaiNguoiDung',
            'TrangThai',
            'Khoa',
            'BoMon_Lop',
            'KhoaHoc',
            'NgayTao',
            'NgayCapNhat',
        ];
    }
}

