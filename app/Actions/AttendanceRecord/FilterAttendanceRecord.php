<?php

namespace App\Actions\AttendanceRecord;

use App\Models\ClassSession;
use App\Models\Subject;
use App\Support\AttendanceRoster;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FilterAttendanceRecord
{
    public function execute(array $data): array
    {
        try {
            DB::beginTransaction();

            $attendanceDate = Carbon::parse($data['date'])->toDateString();
            $dayName = Carbon::parse($attendanceDate)->format('l'); // Monday, Tuesday...

            $user = Auth::user();
            if (!$user) {
                abort(403, 'Unauthorized.');
            }

            $isAdmin = $user->roles()->whereIn('key', ['admin', 'super_admin'])->exists();
            $teacherId = $data['teacher_id'] ?? null;

            if (!$isAdmin) {
                $teacher = $user->teacher;
                if (!$teacher) {
                    abort(403, 'Unauthorized. Teacher profile not found.');
                }
                $teacherId = $teacher->id;
            } elseif (!$teacherId) {
                DB::commit();
                return [
                    'success' => false,
                    'code' => 422,
                    'message' => 'teacher_id is required for admin filter requests.',
                    'data' => null,
                ];
            }

            $query = ClassSession::query()
                ->with([
                    'attendance' => function ($q) use ($attendanceDate) {
                        $q->whereDate('attendance_date', $attendanceDate);
                    },
                ])
                ->where('day_of_week', $dayName)
                ->where('term_id', $data['term_id'])
                ->where('class_id', $data['class_id'])
                ->where('teacher_id', $teacherId);

            if (!empty($data['start_time'])) {
                $query->where('start_time', $data['start_time']);
            }

            if (!empty($data['end_time'])) {
                $query->where('end_time', $data['end_time']);
            }

            $session = $query->first();

            if (!$session) {
                $session = ClassSession::create([
                    'class_id' => $data['class_id'],
                    'term_id' => $data['term_id'],
                    'teacher_id' => $teacherId,
                    'subject_id' => $this->defaultSubjectId(),
                    'day_of_week' => $dayName,
                    'start_time' => $data['start_time'] ?? '00:00:00',
                    'end_time' => $data['end_time'] ?? '00:00:00',
                    'status' => 'scheduled',
                    'created_on' => now(),
                ]);

                $session->load([
                    'attendance' => function ($q) use ($attendanceDate) {
                        $q->whereDate('attendance_date', $attendanceDate);
                    },
                ]);
            }

            $enrollments = AttendanceRoster::eligibleEnrollments((int) $data['class_id']);
            $students = $enrollments->map(function ($enrollment) use ($session) {
                $student = $enrollment->student;
                $record = $session->attendance->firstWhere('student_id', $student->id);

                return [
                    'student_id' => $student->id,
                    'student_code' => $student->student_code,
                    'roll_no' => $student->student_code,
                    'name' => $this->studentName($student),
                    'status' => $record->status ?? null,
                    'comment' => $record->comment ?? '',
                ];
            })->values();

            DB::commit();

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Filter success.',
                'data' => [
                    'class_session_id' => $session->id,
                    'students' => $students,
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    private function defaultSubjectId(): int
    {
        return Subject::query()->value('id')
            ?? Subject::create(['name' => 'General Attendance', 'code' => 'ATTENDANCE'])->id;
    }

    private function studentName($student): string
    {
        $userName = trim($student->user?->name ?? '');
        $profileName = trim(($student->user?->userProfile?->first_name ?? '') . ' ' . ($student->user?->userProfile?->last_name ?? ''));

        if ($userName !== '') {
            return $userName;
        }

        return $profileName !== '' ? $profileName : 'Unknown Student';
    }
}
