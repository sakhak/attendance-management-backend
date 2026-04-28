<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Classes;
use App\Models\ClassSession;
use App\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class ReportTestingDataSeeder extends Seeder
{
    public function run(): void
    {
        $classId = 6; // SET-26-A
        $termId = 4;  // Term 1 2026
        $teacherId = 1; // Vichay Teacher
        $startDate = Carbon::create(2026, 3, 22);
        $endDate = Carbon::create(2026, 4, 22);

        // Get the specific class session for this combination
        $session = ClassSession::where('class_id', $classId)
            ->where('term_id', $termId)
            ->where('teacher_id', $teacherId)
            ->first();

        if (!$session) {
            $this->command->error("Class session not found for Class $classId, Term $termId, Teacher $teacherId");
            return;
        }

        $class = Classes::with('students')->find($classId);
        $students = $class->students;

        if ($students->isEmpty()) {
            $this->command->error("No students found in Class $classId");
            return;
        }

        $period = CarbonPeriod::create($startDate, $endDate);
        $count = 0;

        foreach ($period as $date) {
            // Only seed for weekdays (Monday to Friday)
            if ($date->isWeekend()) {
                continue;
            }

            foreach ($students as $student) {
                // Randomly assign status to make reports look realistic
                $rand = rand(1, 100);
                if ($rand <= 80) {
                    $status = 'present';
                } elseif ($rand <= 90) {
                    $status = 'absent';
                } else {
                    $status = 'permission';
                }

                AttendanceRecord::updateOrCreate(
                    [
                        'class_session_id' => $session->id,
                        'student_id' => $student->id,
                        'attendance_date' => $date->toDateString(),
                    ],
                    [
                        'status' => $status,
                        'recorded_by' => 1, // Admin
                        'comment' => $status !== 'present' ? 'Automated test data' : null,
                    ]
                );
                $count++;
            }
        }

        $this->command->info("Seeded $count attendance records for the report test period!");
    }
}
