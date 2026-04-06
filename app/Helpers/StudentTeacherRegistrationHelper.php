<?php

namespace App\Helpers;

use App\Models\Faculty;
use App\Models\Period;
use Illuminate\Validation\ValidationException;

final class StudentTeacherRegistrationHelper
{
    /**
     * Sinh viên: bắt buộc khoa, niên khóa, lớp; kiểm tra tồn tại bản ghi khoa & niên khóa.
     *
     * @param  array<string, mixed>  $data
     * @return array{faculty_id: int, period_id: int, class_code: string}
     */
    public static function assertAndExtractStudentAffiliation(array $data): array
    {
        $labels = [
            'faculty_id' => __('Khoa'),
            'period_id' => __('Niên khóa'),
            'class_code' => __('Lớp'),
        ];
        $missing = [];
        foreach ($labels as $key => $label) {
            if (! Helpers::filled($data[$key] ?? null)) {
                $missing[$key] = [__('Thiếu thông tin: :field.', ['field' => $label])];
            }
        }
        if ($missing !== []) {
            throw ValidationException::withMessages($missing);
        }

        $facultyId = (int) $data['faculty_id'];
        $periodId = (int) $data['period_id'];

        if (! Faculty::query()->whereKey($facultyId)->exists()) {
            throw ValidationException::withMessages([
                'faculty_id' => [__('Khoa không tồn tại.')],
            ]);
        }
        if (! Period::query()->whereKey($periodId)->exists()) {
            throw ValidationException::withMessages([
                'period_id' => [__('Niên khóa không tồn tại.')],
            ]);
        }

        return [
            'faculty_id' => $facultyId,
            'period_id' => $periodId,
            'class_code' => trim((string) $data['class_code']),
        ];
    }

    /**
     * Giảng viên: bắt buộc khoa; kiểm tra tồn tại bản ghi khoa.
     *
     * @param  array<string, mixed>  $data
     */
    public static function assertAndExtractTeacherFacultyId(array $data): int
    {
        if (! Helpers::filled($data['faculty_id'] ?? null)) {
            throw ValidationException::withMessages([
                'faculty_id' => [__('Thiếu thông tin: :field.', ['field' => __('Khoa')])],
            ]);
        }

        $facultyId = (int) $data['faculty_id'];
        if (! Faculty::query()->whereKey($facultyId)->exists()) {
            throw ValidationException::withMessages([
                'faculty_id' => [__('Khoa không tồn tại.')],
            ]);
        }

        return $facultyId;
    }

    /**
     * Bộ môn / department tùy chọn từ payload (id > 0).
     *
     * @param  array<string, mixed>  $data
     */
    public static function optionalDepartmentId(array $data): ?int
    {
        if (! Helpers::filled($data['department_id'] ?? null)) {
            return null;
        }

        return (int) $data['department_id'];
    }
}
