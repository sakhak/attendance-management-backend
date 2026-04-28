<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestingAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Academic Year 2026-2027 exists
        $academicYear = AcademicYear::where('name', '2026-2027')->first();
        if (!$academicYear) {
            $academicYear = AcademicYear::create([
                'name' => '2026-2027',
                'start_date' => Carbon::create(2026, 1, 1),
                'end_date' => Carbon::create(2026, 12, 31),
            ]);
        }

        // 2. Create Term for 2026
        $term = Term::updateOrCreate(
            ['name' => 'Term 1 2026', 'academic_year_id' => $academicYear->id],
            [
                'start_date' => Carbon::create(2026, 1, 1),
                'end_date' => Carbon::create(2026, 6, 30),
            ]
        );

        // 3. Create Classes for 2026
        $classA = Classes::updateOrCreate(
            ['name' => 'SET-26-A'],
            [
                'grade_level_id' => 1,
                'start_date' => Carbon::create(2026, 1, 1),
                'end_date' => Carbon::create(2026, 6, 30),
                'room_number' => 'R-201',
            ]
        );

        $classB = Classes::updateOrCreate(
            ['name' => 'SET-26-B'],
            [
                'grade_level_id' => 2,
                'start_date' => Carbon::create(2026, 1, 1),
                'end_date' => Carbon::create(2026, 6, 30),
                'room_number' => 'R-202',
            ]
        );

        // 4. Assign Teachers to Classes
        $teacher1 = Teacher::find(1); // Vichay Teacher
        $teacher2 = Teacher::find(2); // Phanna Teacher

        if ($teacher1) {
            $classA->teachers()->syncWithoutDetaching([$teacher1->id]);
        }
        if ($teacher2) {
            $classB->teachers()->syncWithoutDetaching([$teacher2->id]);
        }

        // 5. Enroll Students into these classes
        $students = Student::all();
        
        // Enroll students 1 and 2 in Class A
        foreach ($students->take(2) as $student) {
            Enrollment::updateOrCreate(
                ['class_id' => $classA->id, 'student_id' => $student->id],
                [
                    'grade_level_id' => $classA->grade_level_id,
                    'enrolled_on' => Carbon::create(2026, 1, 15),
                ]
            );
        }

        // Enroll students 3 and 4 in Class B
        foreach ($students->skip(2)->take(2) as $student) {
            Enrollment::updateOrCreate(
                ['class_id' => $classB->id, 'student_id' => $student->id],
                [
                    'grade_level_id' => $classB->grade_level_id,
                    'enrolled_on' => Carbon::create(2026, 1, 15),
                ]
            );
        }

        // 6. Create Class Sessions for current day (Wednesday, April 22, 2026)
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        foreach ($daysOfWeek as $day) {
            // Class A session
            ClassSession::updateOrCreate(
                [
                    'class_id' => $classA->id,
                    'term_id' => $term->id,
                    'teacher_id' => $teacher1->id,
                    'day_of_week' => $day,
                ],
                [
                    'subject_id' => 1, // Mathematics
                    'start_time' => '08:00:00',
                    'end_time' => '10:00:00',
                    'status' => 'scheduled',
                ]
            );

            // Class B session
            ClassSession::updateOrCreate(
                [
                    'class_id' => $classB->id,
                    'term_id' => $term->id,
                    'teacher_id' => $teacher2->id,
                    'day_of_week' => $day,
                ],
                [
                    'subject_id' => 2, // English
                    'start_time' => '10:00:00',
                    'end_time' => '12:00:00',
                    'status' => 'scheduled',
                ]
            );
        }

        // 7. Pre-seed some attendance for today to ensure "not empty" testing
        $today = Carbon::create(2026, 4, 22);
        $sessionA = ClassSession::where('class_id', $classA->id)->where('day_of_week', 'Wednesday')->first();
        
        foreach ($classA->students as $student) {
            AttendanceRecord::updateOrCreate(
                [
                    'class_session_id' => $sessionA->id,
                    'student_id' => $student->id,
                    'attendance_date' => $today->toDateString(),
                ],
                [
                    'status' => 'present',
                    'recorded_by' => 1, // Admin
                ]
            );
        }

        $this->command->info('Testing attendance data for 2026 (including sessions and attendance) seeded successfully!');
    }
}
