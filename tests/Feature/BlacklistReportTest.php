<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BlacklistReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_blacklist_report_returns_only_student_role_users(): void
    {
        $adminRole = Role::create([
            'name' => 'Admin',
            'key' => 'admin',
            'status' => 'active',
        ]);

        $teacherRole = Role::create([
            'name' => 'Teacher',
            'key' => 'teacher',
            'status' => 'active',
        ]);

        $studentRole = Role::create([
            'name' => 'Student',
            'key' => 'student',
            'status' => 'active',
        ]);

        $adminUser = User::factory()->create(['name' => 'Admin User', 'status' => 'active']);
        $adminUser->roles()->attach($adminRole->id);

        $teacherUser = User::factory()->create(['name' => 'Teacher User', 'status' => 'active']);
        $teacherUser->roles()->attach($teacherRole->id);

        $studentUser = User::factory()->create(['name' => 'Student User', 'status' => 'active']);
        $studentUser->roles()->attach($studentRole->id);

        $student = $studentUser->student;
        $student->update([
            'student_code' => 'STU-ONLY',
            'status' => 'active',
        ]);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'teacher_code' => 'T-TEST',
            'status' => 'active',
        ]);

        $academicYear = AcademicYear::create([
            'name' => '2023-2024',
            'start_date' => '2023-09-01',
            'end_date' => '2024-08-31',
        ]);

        $term = Term::create([
            'name' => 'Term 1',
            'academic_year_id' => $academicYear->id,
            'start_date' => '2023-09-01',
            'end_date' => '2024-01-31',
        ]);

        $grade = GradeLevel::create([
            'code' => 'G1',
            'name' => 'Grade 1',
            'order_no' => 1,
            'is_active' => true,
        ]);

        $class = Classes::create([
            'name' => 'Class A',
            'grade_level_id' => $grade->id,
        ]);

        $subject = Subject::create([
            'name' => 'Math',
            'code' => 'MTH',
        ]);

        ClassSession::create([
            'class_id' => $class->id,
            'term_id' => $term->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        Enrollment::create([
            'class_id' => $class->id,
            'student_id' => $student->id,
            'grade_level_id' => $grade->id,
            'enrolled_on' => '2023-09-01',
        ]);

        $adminUser->student->update([
            'student_code' => 'ADMIN-STUDENT-ROW',
            'status' => 'active',
        ]);

        $teacherUser->student->update([
            'student_code' => 'TEACHER-STUDENT-ROW',
            'status' => 'active',
        ]);

        Sanctum::actingAs($adminUser);

        $res = $this->getJson('/api/blacklist?academic_year=2023-2024&absence_threshold=16');

        $res->assertOk()
            ->assertJsonPath('academic_year', '2023-2024')
            ->assertJsonPath('absence_threshold', 16)
            ->assertJsonCount(1, 'students')
            ->assertJsonPath('students.0.student_id', $student->id)
            ->assertJsonPath('students.0.roll_no', 'STU-ONLY')
            ->assertJsonPath('students.0.student_name', 'Student User')
            ->assertJsonPath('students.0.total_absences', 0)
            ->assertJsonPath('students.0.attendance_rate', 100.0)
            ->assertJsonPath('students.0.status', 'Good Standing');

        $studentIds = collect($res->json('students'))->pluck('student_id');

        $this->assertFalse($studentIds->contains($adminUser->student->id));
        $this->assertFalse($studentIds->contains($teacherUser->student->id));

        foreach ($studentIds as $studentId) {
            $this->assertTrue(Student::query()->whereKey($studentId)->exists());
        }
    }
}
