<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceAnalyticsController extends Controller
{
    public function reportSummary(Request $request)
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
        ]);

        $term = Term::findOrFail($validated['term_id']);
        if ((int) $term->academic_year_id !== (int) $validated['academic_year_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Selected term does not belong to selected academic year.',
            ], 422);
        }

        $baseQuery = AttendanceRecord::query()
            ->join('class_sessions as cs', 'cs.id', '=', 'attendance_records.class_session_id')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')
            ->where('cs.term_id', $validated['term_id'])
            ->where('cs.class_id', $validated['class_id'])
            ->whereBetween('attendance_records.created_at', [
                $validated['date_from'] . ' 00:00:00',
                $validated['date_to'] . ' 23:59:59',
            ]);

        $overall = (clone $baseQuery)
            ->selectRaw("
                SUM(CASE WHEN attendance_records.status = 'present' THEN 1 ELSE 0 END) as total_present,
                SUM(CASE WHEN attendance_records.status = 'absent' THEN 1 ELSE 0 END) as total_absent,
                SUM(CASE WHEN attendance_records.status = 'permission' THEN 1 ELSE 0 END) as total_permission,
                COUNT(*) as total_records,
                COUNT(DISTINCT DATE(attendance_records.created_at)) as classes_held
            ")
            ->first();

        $dailyRows = (clone $baseQuery)
            ->selectRaw("
                DATE(attendance_records.created_at) as attendance_date,
                c.name as class_name,
                SUM(CASE WHEN attendance_records.status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN attendance_records.status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN attendance_records.status = 'permission' THEN 1 ELSE 0 END) as permission,
                COUNT(*) as total_records
            ")
            ->groupBy(DB::raw('DATE(attendance_records.created_at)'), 'c.name')
            ->orderByDesc(DB::raw('DATE(attendance_records.created_at)'))
            ->get();

        $daily = $dailyRows->map(function ($row) {
            $total = max((int) $row->total_records, 1);
            $present = (int) $row->present;
            $rate = round(($present / $total) * 100, 1);

            return [
                'date' => Carbon::parse($row->attendance_date)->toDateString(),
                'class_name' => $row->class_name,
                'present' => $present,
                'absent' => (int) $row->absent,
                'permission' => (int) $row->permission,
                'rate' => $rate,
            ];
        })->values();

        $totalRecords = (int) ($overall->total_records ?? 0);
        $totalPresent = (int) ($overall->total_present ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'classes_held' => (int) ($overall->classes_held ?? 0),
                    'avg_attendance' => $totalRecords > 0 ? round(($totalPresent / $totalRecords) * 100, 1) : 0,
                    'total_present' => $totalPresent,
                    'total_absent' => (int) ($overall->total_absent ?? 0),
                    'total_permission' => (int) ($overall->total_permission ?? 0),
                ],
                'daily' => $daily,
            ],
        ]);
    }

    public function blacklistOverview(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            'threshold' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $term = Term::findOrFail($validated['term_id']);
        if ((int) $term->academic_year_id !== (int) $validated['academic_year_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Selected term does not belong to selected academic year.',
            ], 422);
        }

        $threshold = (int) ($validated['threshold'] ?? 16);
        $search = trim((string) ($validated['search'] ?? ''));

        $rows = AttendanceRecord::query()
            ->join('class_sessions as cs', 'cs.id', '=', 'attendance_records.class_session_id')
            ->join('students as s', 's.id', '=', 'attendance_records.student_id')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->where('cs.term_id', $validated['term_id'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('u.name', 'like', "%{$search}%")
                        ->orWhere('s.student_code', 'like', "%{$search}%");
                });
            })
            ->selectRaw("
                s.id as student_id,
                s.student_code as roll_no,
                u.name as student_name,
                SUM(CASE WHEN attendance_records.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN attendance_records.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN attendance_records.status = 'permission' THEN 1 ELSE 0 END) as permission_count,
                COUNT(*) as total_records
            ")
            ->groupBy('s.id', 's.student_code', 'u.name')
            ->orderByDesc('absent_count')
            ->orderBy('u.name')
            ->get()
            ->map(function ($row) use ($threshold) {
                $present = (int) $row->present_count;
                $absent = (int) $row->absent_count;
                $permission = (int) $row->permission_count;
                $total = max((int) $row->total_records, 1);
                $attendanceRate = round(($present / $total) * 100, 1);

                $status = 'good_standing';
                if ($absent >= $threshold) {
                    $status = 'blacklisted';
                } elseif ($absent >= max(1, $threshold - 2)) {
                    $status = 'warning';
                }

                return [
                    'student_id' => (int) $row->student_id,
                    'roll_no' => $row->roll_no ?: 'N/A',
                    'student_name' => $row->student_name ?: 'Unknown Student',
                    'total_absences' => $absent,
                    'attendance_rate' => $attendanceRate,
                    'status' => $status,
                    'present_count' => $present,
                    'permission_count' => $permission,
                ];
            })
            ->values();

        $blacklistedCount = $rows->where('status', 'blacklisted')->count();
        $warningCount = $rows->where('status', 'warning')->count();
        $goodStandingCount = $rows->where('status', 'good_standing')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'threshold' => $threshold,
                    'total_students' => $rows->count(),
                    'blacklisted_count' => $blacklistedCount,
                    'warning_count' => $warningCount,
                    'good_standing_count' => $goodStandingCount,
                ],
                'rows' => $rows,
            ],
        ]);
    }
}
