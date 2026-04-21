<?php

namespace App\Actions\AttendanceRecord;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAttendanceRecord
{
    /**
     * Delete attendance for multiple students in one class session.
     * Expected $data:
     * [
     *   'class_session_id' => 1,
     *   'student_ids' => [10, 11, 12]
     * ]
     */
    public function execute(array $data): array
    {
        try {
            DB::beginTransaction();

            $attendanceDate = Carbon::parse($data['date'])->toDateString();

            $deleted = AttendanceRecord::query()
                ->where('class_session_id', $data['class_session_id'])
                ->whereDate('attendance_date', $attendanceDate)
                ->whereIn('student_id', $data['student_ids'])
                ->delete();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Attendance deleted successfully.',
                'deleted' => $deleted,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
