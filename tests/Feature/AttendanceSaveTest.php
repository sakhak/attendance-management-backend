<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceSaveTest extends TestCase
{
    use RefreshDatabase;

    private function setUpAttendanceContext(): array
    {
        Carbon::setTestNow(Carbon::parse('2026-04-21 09:00:00'));

        $teacherRole = Role::create([
            'name' => 'Teacher',
            'key' => 'teacher',
            'status' => 'active',
        ]);

        $adminRole = Role::create([
            'name' => 'Admin',
            'key' => 'admin',
            'status' => 'active',
        ]);

        $teacherUser = User::factory()->create(['status' => 'active']);
        $teacherUser->roles()->attach($teacherRole->id);

        $otherTeacherUser = User::factory()->create(['status' => 'active']);
        $otherTeacherUser->roles()->attach($teacherRole->id);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'teacher_code' => 'TEACH-T1',
            'status' => 'active',
        ]);

        $otherTeacher = Teacher::create([
            'user_id' => $otherTeacherUser->id,
            'teacher_code' => 'TEACH-T2',
            'status' => 'active',
        ]);

        $year = AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-08-31',
        ]);

        $term = Term::create([
            'name' => 'Term 1',
            'academic_year_id' => $year->id,
            'start_date' => '2025-09-01',
            'end_date' => '2026-01-31',
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
            'start_date' => '2025-09-01',
            'end_date' => '2026-01-31',
            'room_number' => 'A1',
        ]);

        $class->teachers()->attach($teacher->id);

        $subject = Subject::create([
            'name' => 'Math',
            'code' => 'MTH',
        ]);

        $session = ClassSession::create([
            'class_id' => $class->id,
            'term_id' => $term->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'status' => 'scheduled',
        ]);

        $studentUser1 = User::factory()->create(['status' => 'active']);
        $studentUser1->roles()->attach($adminRole->id); // role doesn't matter for roster, keep it simple
        $student1 = Student::create([
            'user_id' => $studentUser1->id,
            'student_code' => 'S-001',
            'status' => 'active',
        ]);

        $studentUser2 = User::factory()->create(['status' => 'active']);
        $student2 = Student::create([
            'user_id' => $studentUser2->id,
            'student_code' => 'S-002',
            'status' => 'active',
        ]);

        Enrollment::create([
            'class_id' => $class->id,
            'student_id' => $student1->id,
            'grade_level_id' => $grade->id,
            'enrolled_on' => '2025-09-01',
        ]);

        Enrollment::create([
            'class_id' => $class->id,
            'student_id' => $student2->id,
            'grade_level_id' => $grade->id,
            'enrolled_on' => '2025-09-01',
        ]);

        return compact(
            'teacherUser',
            'otherTeacher',
            'otherTeacherUser',
            'session',
            'student1',
            'student2'
        );
    }

    public function test_teacher_can_save_attendance_for_assigned_session_and_date(): void
    {
        $ctx = $this->setUpAttendanceContext();

        Sanctum::actingAs($ctx['teacherUser']);

        $payload = [
            'class_session_id' => $ctx['session']->id,
            'date' => '2026-04-20', // Monday, <= "today"
            'records' => [
                ['student_id' => $ctx['student1']->id, 'status' => 'present'],
                ['student_id' => $ctx['student2']->id, 'status' => 'absent', 'comment' => 'Sick'],
            ],
        ];

        $res = $this->postJson('/api/attendance-records', $payload);
        $res->assertStatus(201);

        $this->assertDatabaseHas('attendance_records', [
            'class_session_id' => $ctx['session']->id,
            'student_id' => $ctx['student1']->id,
            'attendance_date' => '2026-04-20',
            'status' => 'present',
        ]);
    }

    public function test_cannot_save_future_date(): void
    {
        $ctx = $this->setUpAttendanceContext();

        Sanctum::actingAs($ctx['teacherUser']);

        $payload = [
            'class_session_id' => $ctx['session']->id,
            'date' => '2026-04-22', // future
            'records' => [
                ['student_id' => $ctx['student1']->id, 'status' => 'present'],
                ['student_id' => $ctx['student2']->id, 'status' => 'present'],
            ],
        ];

        $res = $this->postJson('/api/attendance-records', $payload);
        $res->assertStatus(422);
    }

    public function test_cannot_partial_save_missing_students(): void
    {
        $ctx = $this->setUpAttendanceContext();

        Sanctum::actingAs($ctx['teacherUser']);

        $payload = [
            'class_session_id' => $ctx['session']->id,
            'date' => '2026-04-20',
            'records' => [
                ['student_id' => $ctx['student1']->id, 'status' => 'present'],
            ],
        ];

        $res = $this->postJson('/api/attendance-records', $payload);
        $res->assertStatus(422);
    }

    public function test_teacher_cannot_save_for_unassigned_session(): void
    {
        $ctx = $this->setUpAttendanceContext();

        // Change the session's assigned teacher to someone else.
        $ctx['session']->update(['teacher_id' => $ctx['otherTeacher']->id]);

        Sanctum::actingAs($ctx['teacherUser']);

        $payload = [
            'class_session_id' => $ctx['session']->id,
            'date' => '2026-04-20',
            'records' => [
                ['student_id' => $ctx['student1']->id, 'status' => 'present'],
                ['student_id' => $ctx['student2']->id, 'status' => 'present'],
            ],
        ];

        $res = $this->postJson('/api/attendance-records', $payload);
        $res->assertStatus(403);
    }
}

