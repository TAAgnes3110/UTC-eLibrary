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
                ['code' => 'CNTT-K1', 'name' => 'Khoa học máy tính'],
                ['code' => 'CNTT-K2', 'name' => 'Hệ thống thông tin'],
                ['code' => 'CNTT-K3', 'name' => 'Mạng máy tính'],
            ],
            'CKD' => [
                ['code' => 'CKD-K1', 'name' => 'Động cơ đốt trong'],
                ['code' => 'CKD-K2', 'name' => 'Ô tô'],
            ],
            'XD' => [
                ['code' => 'XD-K1', 'name' => 'Cầu đường'],
                ['code' => 'XD-K2', 'name' => 'Xây dựng dân dụng'],
            ],
            'KT' => [
                ['code' => 'KT-K1', 'name' => 'Kinh tế vận tải đường bộ'],
                ['code' => 'KT-K2', 'name' => 'Logistics'],
            ],
            'CKVT' => [
                ['code' => 'CKVT-K1', 'name' => 'Cơ khí đường sắt'],
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
