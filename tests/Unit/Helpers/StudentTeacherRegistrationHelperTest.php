<?php

namespace Tests\Unit\Helpers;

use App\Helpers\StudentTeacherRegistrationHelper;
use App\Models\Faculty;
use App\Models\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StudentTeacherRegistrationHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_affiliation_returns_normalized_values(): void
    {
        $faculty = Faculty::query()->create([
            'code' => 'F1',
            'name' => 'Khoa 1',
            'is_active' => true,
        ]);
        $period = Period::query()->create([
            'code' => 'P1',
            'name' => 'Niên khóa 1',
            'is_active' => true,
        ]);

        $out = StudentTeacherRegistrationHelper::assertAndExtractStudentAffiliation([
            'faculty_id' => $faculty->id,
            'period_id' => $period->id,
            'class_code' => '  L-A1  ',
        ]);

        $this->assertSame($faculty->id, $out['faculty_id']);
        $this->assertSame($period->id, $out['period_id']);
        $this->assertSame('L-A1', $out['class_code']);
    }

    public function test_teacher_faculty_throws_when_missing(): void
    {
        $this->expectException(ValidationException::class);
        StudentTeacherRegistrationHelper::assertAndExtractTeacherFacultyId([]);
    }
}
