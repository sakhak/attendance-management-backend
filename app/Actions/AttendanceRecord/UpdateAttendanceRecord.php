<?php

namespace App\Actions\AttendanceRecord;

use App\Models\AttendanceRecord;
use App\Models\Blacklist;
use App\Models\ClassSession;
use App\Support\AttendanceRoster;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UpdateAttendanceRecord
{
    public function execute(array $data)
    {
        DB::beginTransaction();

        try {
            $classSessionId = $data['class_session_id'];
            $records = $data['records'];
            $attendanceDate = Carbon::parse($data['date'])->toDateString();

            if (Carbon::parse($attendanceDate)->isFuture()) {
                throw ValidationException::withMessages([
                    'date' => ['Attendance date cannot be in the future.'],
                ]);
            }

            $user = Auth::user();
            if (!$user) {
                abort(403, 'Unauthorized.');
            }

            $isAdmin = $user->roles()->whereIn('key', ['admin', 'super_admin'])->exists();

            /** @var \App\Models\ClassSession $session */
            $session = ClassSession::query()
                ->with('class.teachers')
                ->findOrFail($classSessionId);

            $dayName = Carbon::parse($attendanceDate)->format('l');
            if ($session->day_of_week !== $dayName) {
                throw ValidationException::withMessages([
                    'date' => ["Selected date is {$dayName} but this class session is scheduled for {$session->day_of_week}."],
                ]);
            }

            if (!$isAdmin) {
                $teacher = $user->teacher;
                if (!$teacher) {
                    abort(403, 'Unauthorized. Teacher profile not found.');
                }

                if ((int) $teacher->id !== (int) $session->teacher_id) {
                    abort(403, 'Unauthorized. You are not assigned to this class session.');
                }

                $isAssignedToClass = $session->class
                    ->teachers()
                    ->where('teachers.id', $teacher->id)
                    ->exists();

                if (!$isAssignedToClass) {
                    abort(403, 'Unauthorized. You are not assigned to this class.');
                }
            }

            $enrolledStudentIds = AttendanceRoster::eligibleStudentIds((int) $session->class_id);

            if ($enrolledStudentIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'records' => ['No enrolled students found for this class.'],
                ]);
            }

            $submittedStudentIds = collect($records)->pluck('student_id')->values();

            if ($submittedStudentIds->count() !== $submittedStudentIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'records' => ['Duplicate student_id found in records.'],
                ]);
            }

            $missing = $enrolledStudentIds->diff($submittedStudentIds)->values();
            $extra = $submittedStudentIds->diff($enrolledStudentIds)->values();

            if ($missing->isNotEmpty() || $extra->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'records' => ['Records must include all enrolled students (no missing/extra).'],
                ]);
            }

            $userId = $user->id;
            $now = now();

            $rows = collect($records)->map(function ($record) use ($classSessionId, $attendanceDate, $userId, $now) {
                return [
                    'class_session_id' => $classSessionId,
                    'student_id' => $record['student_id'],
                    'recorded_by' => $userId,
                    'attendance_date' => $attendanceDate,
                    'status' => $record['status'],
                    'comment' => $record['comment'] ?? null,
                    'updated_at' => $now,
                ];
            })->toArray();

            AttendanceRecord::upsert(
                $rows,
                ['class_session_id', 'student_id', 'attendance_date'],
                ['recorded_by', 'status', 'comment', 'updated_at']
            );

            $absenceCounts = AttendanceRecord::query()
                ->whereIn('student_id', $enrolledStudentIds->all())
                ->where('status', 'absent')
                ->whereHas('classSession', function (Builder $q) use ($session) {
                    $q->where('term_id', $session->term_id);
                })
                ->select('student_id', DB::raw('COUNT(*) as absence_count'))
                ->groupBy('student_id')
                ->pluck('absence_count', 'student_id');

            foreach ($enrolledStudentIds as $studentId) {
                $count = (int) ($absenceCounts[$studentId] ?? 0);

                if ($count >= 16) {
                    Blacklist::updateOrCreate(
                        ['student_id' => $studentId, 'term_id' => $session->term_id],
                        ['absence_count' => $count, 'flagged_at' => $now]
                    );
                } else {
                    Blacklist::query()
                        ->where('student_id', $studentId)
                        ->where('term_id', $session->term_id)
                        ->delete();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Attendance updated successfully.',
                'count' => count($rows),
                'attendance_date' => $attendanceDate,
                'class_session_id' => $classSessionId,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
