<?php

namespace App\Actions\AttendanceRecord;

use App\Models\ClassSession;
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
                    'class.students',
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
                DB::commit();
                return [
                    'success' => false,
                    'code' => 404,
                    'message' => "No class session found. (date day = {$dayName})",
                    'data' => null,
                ];
            }

            $students = $session->class->students->map(function ($student) use ($session) {
                $record = $session->attendance->firstWhere('student_id', $student->id);

                return [
                    'student_id' => $student->id,
                    'name' => trim(($student->last_name ?? '') . ', ' . ($student->first_name ?? '')),
                    'status' => $record->status ?? null,
                    'comment' => $record->comment ?? null,
                ];
            })->values();

            DB::commit();

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Filter success.',
                'data' => [
                    'class_session_id' => $session->id,
                    'attendance_date' => $attendanceDate,
                    'day_of_week' => $dayName,
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
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
}

