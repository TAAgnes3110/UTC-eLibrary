<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Database\Seeder;

/** Dữ liệu mẫu: Lớp/Bộ môn (theo khoa). */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $byFaculty = [
            'CNTT' => [
                ['code' => 'CNTT-KHMT', 'name' => 'Khoa học máy tính'],
                ['code' => 'CNTT-HTTT', 'name' => 'Hệ thống thông tin'],
                ['code' => 'CNTT-KTMT', 'name' => 'Kỹ thuật máy tính'],
            ],
            'CT_XDCTGT' => [
                ['code' => 'CTGT-CauDuong', 'name' => 'Bộ môn Cầu đường bộ'],
                ['code' => 'CTGT-DuongSat', 'name' => 'Bộ môn Đường sắt – Metro'],
            ],
            'VT' => [
                ['code' => 'VT-KTVT', 'name' => 'Bộ môn Kinh tế vận tải'],
                ['code' => 'VT-Logistics', 'name' => 'Bộ môn Logistics & Quản lý chuỗi cung ứng'],
            ],
            'KTXD' => [
                ['code' => 'KTXD-DanDung', 'name' => 'Bộ môn Xây dựng dân dụng'],
                ['code' => 'KTXD-CongNghiep', 'name' => 'Bộ môn Xây dựng công nghiệp'],
            ],
            'MT_ATGT' => [
                ['code' => 'MTATGT-ATGT', 'name' => 'Bộ môn An toàn giao thông'],
                ['code' => 'MTATGT-MoiTruong', 'name' => 'Bộ môn Môi trường giao thông'],
            ],
        ];

        foreach ($byFaculty as $facultyCode => $departments) {
            $faculty = Faculty::where('code', $facultyCode)->first();
            if (!$faculty) {
                continue;
            }
            foreach ($departments as $item) {
                Department::firstOrCreate(
                    ['code' => $item['code']],
                    [
                        'faculty_id' => $faculty->id,
                        'name' => $item['name'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
