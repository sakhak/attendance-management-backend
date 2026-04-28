<?php

namespace App\Http\Controllers;

use App\Actions\Blacklist\CreateBlacklist;
use App\Actions\Blacklist\DeleteBlacklist;
use App\Actions\Blacklist\UpdateBlacklist;
use App\Models\AttendanceRuleSetting;
use App\Models\Blacklist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BlacklistController extends Controller
{
    private const STUDENT_ROLE = 'student';

    /**
     * Display the absence-based blacklist report for active students.
     */
    public function report(Request $request)
    {
        $validated = $request->validate([
            'academic_year' => ['nullable', 'string', 'max:50'],
            'absence_threshold' => ['nullable', 'integer', 'min:0'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $academicYear = $validated['academic_year'] ?? null;
        $absenceThreshold = (int) ($validated['absence_threshold'] ?? $this->defaultAbsenceThreshold());
        $students = $this->blacklistRows($academicYear, $absenceThreshold, $validated['search'] ?? null);

        return response()->json([
            'academic_year' => $academicYear,
            'absence_threshold' => $absenceThreshold,
            'summary' => $this->summary($students),
            'students' => $students,
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'academic_year' => ['nullable', 'string', 'max:50'],
            'absence_threshold' => ['nullable', 'integer', 'min:0'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $academicYear = $validated['academic_year'] ?? null;
        $absenceThreshold = (int) ($validated['absence_threshold'] ?? $this->defaultAbsenceThreshold());
        $students = $this->blacklistRows($academicYear, $absenceThreshold, $validated['search'] ?? null);
        $filename = 'blacklist-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($students) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Roll No', 'Student Name', 'Total Absences', 'Attendance Rate', 'Status']);

            foreach ($students as $student) {
                fputcsv($handle, [
                    $student['roll_no'],
                    $student['student_name'],
                    $student['total_absences'],
                    $student['attendance_rate'],
                    $student['status'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $blacklists = Blacklist::with(['student.user', 'term'])->get();

        return response()->json([
            'list' => [
                'data' => $blacklists,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $action = new CreateBlacklist();
            $blacklist = $action->create($request);

            return response()->json([
                'data' => $blacklist,
                'message' => 'Blacklist created successfully',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating blacklist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $blacklist = Blacklist::findOrFail($id);

        return response()->json([
            'data' => $blacklist,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Blacklist $blacklist)
    {
        try {
            $action = new UpdateBlacklist();
            $updatedBlacklist = $action->update($request, $blacklist);

            return response()->json([
                'data' => $updatedBlacklist,
                'message' => 'Blacklist updated successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating blacklist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blacklist $blacklist)
    {
        try {
            $action = new DeleteBlacklist();
            $action->delete($blacklist);

            return response()->json([
                'data' => $blacklist,
                'message' => 'Blacklist deleted successfully',
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Cannot delete blacklist',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting blacklist',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function blacklistRows(?string $academicYear, int $absenceThreshold, ?string $search): array
    {
        $attendanceCounts = DB::table('attendance_records')
            ->select('attendance_records.student_id')
            ->selectRaw('COUNT(*) as total_attendance_records')
            ->selectRaw("SUM(CASE WHEN attendance_records.status = 'absent' THEN 1 ELSE 0 END) as total_absences")
            ->selectRaw("SUM(CASE WHEN attendance_records.status = 'present' THEN 1 ELSE 0 END) as present_count")
            ->join('class_sessions', 'class_sessions.id', '=', 'attendance_records.class_session_id')
            ->join('terms', 'terms.id', '=', 'class_sessions.term_id');

        if ($academicYear !== null && Schema::hasTable('academic_years')) {
            $attendanceCounts
                ->join('academic_years', 'academic_years.id', '=', 'terms.academic_year_id')
                ->where('academic_years.name', $academicYear);
        }

        $attendanceCounts->groupBy('attendance_records.student_id');

        $studentNameExpression = $this->studentNameExpression();

        $query = DB::table('students')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->leftJoinSub($attendanceCounts, 'attendance_counts', function ($join) {
                $join->on('attendance_counts.student_id', '=', 'students.id');
            })
            ->where('students.status', 'active')
            ->when($academicYear !== null && Schema::hasTable('academic_years'), function ($query) use ($academicYear) {
                $query->whereExists(function ($query) use ($academicYear) {
                    $query
                        ->selectRaw('1')
                        ->from('enrollments')
                        ->join('class_sessions', 'class_sessions.class_id', '=', 'enrollments.class_id')
                        ->join('terms', 'terms.id', '=', 'class_sessions.term_id')
                        ->join('academic_years', 'academic_years.id', '=', 'terms.academic_year_id')
                        ->whereColumn('enrollments.student_id', 'students.id')
                        ->where('academic_years.name', $academicYear);
                });
            })
            ->whereExists(function ($query) {
                $query
                    ->selectRaw('1')
                    ->from('user_role')
                    ->join('roles', 'roles.id', '=', 'user_role.role_id')
                    ->whereColumn('user_role.user_id', 'users.id')
                    ->where(function ($query) {
                        $query
                            ->whereRaw('LOWER(roles.name) = ?', [self::STUDENT_ROLE])
                            ->orWhereRaw('LOWER(roles.key) = ?', [self::STUDENT_ROLE]);
                    });
            })
            ->whereNotExists(function ($query) {
                $query
                    ->selectRaw('1')
                    ->from('user_role')
                    ->join('roles', 'roles.id', '=', 'user_role.role_id')
                    ->whereColumn('user_role.user_id', 'users.id')
                    ->where(function ($query) {
                        $query
                            ->whereRaw('LOWER(roles.name) != ?', [self::STUDENT_ROLE])
                            ->whereRaw('LOWER(roles.key) != ?', [self::STUDENT_ROLE]);
                    });
            })
            ->select([
                'students.id as student_id',
                'students.student_code',
                'users.name as user_name',
                'user_profiles.first_name',
                'user_profiles.last_name',
                DB::raw('COALESCE(attendance_counts.total_attendance_records, 0) as total_attendance_records'),
                DB::raw('COALESCE(attendance_counts.total_absences, 0) as total_absences'),
                DB::raw('COALESCE(attendance_counts.present_count, 0) as present_count'),
            ]);

        if (Schema::hasColumn('students', 'roll_no')) {
            $query->addSelect('students.roll_no');
        }

        if (Schema::hasColumn('students', 'name')) {
            $query->addSelect('students.name as student_table_name');
        }

        if ($search !== null && $search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($query) use ($like, $studentNameExpression) {
                $query
                    ->where('students.student_code', 'like', $like)
                    ->orWhere('students.id', 'like', $like)
                    ->orWhere('users.name', 'like', $like)
                    ->orWhere('user_profiles.first_name', 'like', $like)
                    ->orWhere('user_profiles.last_name', 'like', $like)
                    ->orWhereRaw($studentNameExpression . ' like ?', [$like]);

                if (Schema::hasColumn('students', 'roll_no')) {
                    $query->orWhere('students.roll_no', 'like', $like);
                }

                if (Schema::hasColumn('students', 'name')) {
                    $query->orWhere('students.name', 'like', $like);
                }
            });
        }

        return $query
            ->orderByDesc('total_absences')
            ->orderBy('students.id')
            ->get()
            ->map(fn ($student) => $this->formatStudentRow($student, $absenceThreshold))
            ->all();
    }

    private function formatStudentRow(object $student, int $absenceThreshold): array
    {
        $totalRecords = (int) $student->total_attendance_records;
        $presentCount = (int) $student->present_count;
        $totalAbsences = (int) $student->total_absences;

        return [
            'student_id' => (int) $student->student_id,
            'roll_no' => $student->roll_no ?? $student->student_code ?? (string) $student->student_id,
            'student_name' => $this->studentName($student),
            'total_absences' => $totalAbsences,
            'attendance_rate' => $totalRecords === 0 ? 100.0 : round(($presentCount / $totalRecords) * 100, 1),
            'status' => $this->blacklistStatus($totalAbsences, $absenceThreshold),
        ];
    }

    private function studentName(object $student): string
    {
        $profileName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

        return $student->user_name
            ?? ($profileName !== '' ? $profileName : null)
            ?? $student->student_table_name
            ?? 'Student #' . $student->student_id;
    }

    private function blacklistStatus(int $totalAbsences, int $absenceThreshold): string
    {
        if ($totalAbsences >= $absenceThreshold) {
            return 'Blacklisted';
        }

        if ($totalAbsences >= $absenceThreshold - 4) {
            return 'Warning';
        }

        return 'Good Standing';
    }

    private function summary(array $students): array
    {
        return [
            'blacklisted_count' => collect($students)->where('status', 'Blacklisted')->count(),
            'warning_count' => collect($students)->where('status', 'Warning')->count(),
            'good_standing_count' => collect($students)->where('status', 'Good Standing')->count(),
            'total_students' => count($students),
        ];
    }

    private function defaultAbsenceThreshold(): int
    {
        if (class_exists(AttendanceRuleSetting::class) && Schema::hasTable('attendance_rule_settings')) {
            return AttendanceRuleSetting::query()->value('absent_threshold_minutes') ?? 16;
        }

        return 16;
    }

    private function studentNameExpression(): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "trim(coalesce(user_profiles.first_name, '') || ' ' || coalesce(user_profiles.last_name, ''))"
            : "trim(concat(coalesce(user_profiles.first_name, ''), ' ', coalesce(user_profiles.last_name, '')))";
    }
}
