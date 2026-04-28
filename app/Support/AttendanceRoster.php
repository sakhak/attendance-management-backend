<?php

namespace App\Support;

use App\Models\Enrollment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AttendanceRoster
{
    public const STUDENT_STATUS_ACTIVE = 'active';
    private const STUDENT_ROLE = 'student';

    public static function eligibleEnrollments(int $classId): Collection
    {
        $query = Enrollment::query()
            ->where('enrollments.class_id', $classId)
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->leftJoin('users', 'users.id', '=', 'students.user_id')
            ->whereHas('student')
            ->whereHas('student.user')
            ->where('students.status', self::STUDENT_STATUS_ACTIVE)
            ->whereHas('student.user.roles', function ($query) {
                $query->where(function ($query) {
                    $query
                        ->whereRaw('LOWER(roles.name) = ?', [self::STUDENT_ROLE])
                        ->orWhereRaw('LOWER(roles.key) = ?', [self::STUDENT_ROLE]);
                });
            })
            ->whereDoesntHave('student.user.roles', function ($query) {
                $query->where(function ($query) {
                    $query
                        ->whereRaw('LOWER(roles.name) != ?', [self::STUDENT_ROLE])
                        ->whereRaw('LOWER(roles.key) != ?', [self::STUDENT_ROLE]);
                });
            })
            ->with(['student.user.userProfile'])
            ->select('enrollments.*')
            ->orderBy('students.student_code')
            ->orderBy('users.name');

        if (Schema::hasColumn('enrollments', 'status')) {
            $query->where('enrollments.status', 'active');
        }

        return $query->get();
    }

    public static function eligibleStudentIds(int $classId): Collection
    {
        return self::eligibleEnrollments($classId)
            ->pluck('student_id')
            ->values();
    }
}
