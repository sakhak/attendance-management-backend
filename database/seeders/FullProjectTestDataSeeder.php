<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRuleSetting;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FullProjectTestDataSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        $roles = $this->roles();

        $admin = $this->user('test.admin@example.com', 'Test Admin', $roles['admin']);
        $teacherUser = $this->user('test.teacher@example.com', 'Test Teacher', $roles['teacher']);
        $teacher = Teacher::updateOrCreate(
            ['user_id' => $teacherUser->id],
            ['teacher_code' => 'TCH-TEST-001', 'status' => 'active']
        );

        $academicYear = AcademicYear::updateOrCreate(
            ['name' => '2023-2024'],
            ['start_date' => '2023-09-01', 'end_date' => '2024-08-31']
        );

        $term = Term::updateOrCreate(
            ['name' => 'Term 1', 'academic_year_id' => $academicYear->id],
            ['start_date' => '2023-09-01', 'end_date' => '2024-01-31']
        );

        $grade = GradeLevel::updateOrCreate(
            ['code' => 'TEST-G1'],
            ['name' => 'Test Grade 1', 'order_no' => 1, 'is_active' => true]
        );

        $subject = Subject::updateOrCreate(
            ['code' => 'TEST-ATT'],
            ['name' => 'Test Attendance']
        );

        $class = Classes::updateOrCreate(
            ['name' => 'TEST-BLACKLIST-A'],
            [
                'grade_level_id' => $grade->id,
                'start_date' => '2023-09-01',
                'end_date' => '2024-01-31',
                'room_number' => 'TEST-101',
                'schedule_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            ]
        );
        $class->teachers()->syncWithoutDetaching([$teacher->id]);

        $sessions = collect(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])
            ->mapWithKeys(fn (string $day) => [
                $day => ClassSession::updateOrCreate(
                    [
                        'class_id' => $class->id,
                        'term_id' => $term->id,
                        'teacher_id' => $teacher->id,
                        'day_of_week' => $day,
                        'start_time' => '08:00:00',
                    ],
                    [
                        'subject_id' => $subject->id,
                        'end_time' => '09:00:00',
                        'status' => 'scheduled',
                    ]
                ),
            ]);

        $students = [
            ['code' => 'STU-TEST-001', 'name' => 'William Garcia', 'email' => 'william.garcia@test.local', 'absences' => 21],
            ['code' => 'STU-TEST-002', 'name' => 'Sophia Chen', 'email' => 'sophia.chen@test.local', 'absences' => 18],
            ['code' => 'STU-TEST-003', 'name' => 'Noah Kim', 'email' => 'noah.kim@test.local', 'absences' => 17],
            ['code' => 'STU-TEST-004', 'name' => 'Emma Patel', 'email' => 'emma.patel@test.local', 'absences' => 16],
            ['code' => 'STU-TEST-005', 'name' => 'Liam Brown', 'email' => 'liam.brown@test.local', 'absences' => 15],
            ['code' => 'STU-TEST-006', 'name' => 'Olivia Smith', 'email' => 'olivia.smith@test.local', 'absences' => 14],
            ['code' => 'STU-TEST-007', 'name' => 'Ethan Nguyen', 'email' => 'ethan.nguyen@test.local', 'absences' => 13],
            ['code' => 'STU-TEST-008', 'name' => 'Ava Johnson', 'email' => 'ava.johnson@test.local', 'absences' => 12],
            ['code' => 'STU-TEST-009', 'name' => 'Mia Davis', 'email' => 'mia.davis@test.local', 'absences' => 3],
            ['code' => 'STU-TEST-010', 'name' => 'James Wilson', 'email' => 'james.wilson@test.local', 'absences' => 0],
        ];

        foreach ($students as $studentData) {
            $user = $this->user($studentData['email'], $studentData['name'], $roles['student']);
            $student = $this->student($user, $studentData['code']);
            $this->enroll($class, $student);
            $this->attendance($sessions['Monday'], $student, $teacherUser, $studentData['absences']);
        }

        // Enrolled non-student users with heavy absences prove role filters exclude them.
        $enrolledAdminStudent = $this->student($admin, 'STU-TEST-ADMIN');
        $this->enroll($class, $enrolledAdminStudent);
        $this->attendance($sessions['Monday'], $enrolledAdminStudent, $teacherUser, 25);

        $enrolledTeacherStudent = $this->student($teacherUser, 'STU-TEST-TEACHER');
        $this->enroll($class, $enrolledTeacherStudent);
        $this->attendance($sessions['Monday'], $enrolledTeacherStudent, $teacherUser, 25);

        SchoolSetting::firstOrCreate(['id' => 1], [
            'school_name' => 'SETEC Institute',
            'address' => 'Phnom Penh',
            'city' => 'Phnom Penh',
            'state_province' => 'Phnom Penh',
            'postal_code' => '62704',
            'country' => 'Cambodia',
            'academic_year' => '2023-2024',
            'term_semester' => 'Term 1',
        ]);

        AttendanceRuleSetting::updateOrCreate(['id' => 1], [
            'late_threshold_minutes' => 15,
            'absent_threshold_minutes' => 16,
            'exclude_weekends' => true,
            'auto_exclude_public_holidays' => true,
        ]);

        $this->command->info('Full project test data imported.');
        $this->command->line("Admin login: test.admin@example.com / " . self::PASSWORD);
        $this->command->line("Teacher login: test.teacher@example.com / " . self::PASSWORD);
        $this->command->line("Academic year: {$academicYear->name}");
        $this->command->line("Term ID: {$term->id}");
        $this->command->line("Class ID: {$class->id}");
        $this->command->line("Teacher ID: {$teacher->id}");
        $this->command->line('Blacklist expected with threshold 16: 4 Blacklisted, 4 Warning, 2 Good Standing.');
    }

    private function roles(): array
    {
        $definitions = [
            'admin' => ['name' => 'Admin', 'key' => 'admin', 'description' => 'Test admin'],
            'teacher' => ['name' => 'Teacher', 'key' => 'teacher', 'description' => 'Test teacher'],
            'student' => ['name' => 'Student', 'key' => 'student', 'description' => 'Test student'],
        ];

        return collect($definitions)
            ->map(fn (array $role) => Role::updateOrCreate(
                ['key' => $role['key']],
                ['name' => $role['name'], 'status' => 'active', 'description' => $role['description']]
            ))
            ->all();
    }

    private function user(string $email, string $name, Role $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make(self::PASSWORD), 'status' => 'active']
        );

        $user->roles()->sync([$role->id]);

        return $user->refresh();
    }

    private function student(User $user, string $studentCode): Student
    {
        return Student::updateOrCreate(
            ['user_id' => $user->id],
            ['student_code' => $studentCode, 'status' => 'active']
        );
    }

    private function enroll(Classes $class, Student $student): void
    {
        Enrollment::updateOrCreate(
            ['class_id' => $class->id, 'student_id' => $student->id],
            ['grade_level_id' => $class->grade_level_id, 'enrolled_on' => '2023-09-01']
        );
    }

    private function attendance(ClassSession $session, Student $student, User $teacherUser, int $absences): void
    {
        $start = Carbon::parse('2023-09-04');

        for ($i = 0; $i < 30; $i++) {
            $date = $start->copy()->addWeeks($i)->toDateString();
            $status = $i < $absences ? 'absent' : 'present';

            AttendanceRecord::updateOrCreate(
                [
                    'class_session_id' => $session->id,
                    'student_id' => $student->id,
                    'attendance_date' => $date,
                ],
                [
                    'recorded_by' => $teacherUser->id,
                    'status' => $status,
                    'comment' => $status === 'absent' ? 'Test absence' : '',
                ]
            );
        }
    }
}
