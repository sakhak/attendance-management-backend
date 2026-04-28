<?php

namespace App\Http\Controllers;

use App\Actions\AttendanceRecord\CreateAttendanceRecord;
use App\Actions\AttendanceRecord\DeleteAttendanceRecord;
use App\Actions\AttendanceRecord\FilterAttendanceRecord;
use App\Actions\AttendanceRecord\UpdateAttendanceRecord;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = AttendanceRecord::query()
                ->with(['student', 'classSession', 'recordedBy']);

            // optional filters
            if ($request->filled('class_session_id')) {
                $query->where('class_session_id', $request->class_session_id);
            }

            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status); // present|absent|permission
            }

            if ($request->filled('attendance_date')) {
                $query->whereDate('attendance_date', $request->attendance_date);
            }

            // return data
            $records = $query->latest()->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $records
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function filter(Request $request, FilterAttendanceRecord $action)
    {
        $validated = $request->validate([
            'date'       => ['required', 'date'],
            'term_id'    => ['required', 'integer', 'exists:terms,id'],
            'class_id'   => ['required', 'integer', 'exists:classes,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],

            // Optional (for time range filtering)
            'start_time' => ['nullable', 'date_format:H:i:s'],
            'end_time'   => ['nullable', 'date_format:H:i:s'],
        ]);

        $result = $action->execute($validated);

        return response()->json([
            'data' => $result['data'],
        ], $result['code']);
    }

    public function report(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => ['required', 'date'],
                'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
                'term_id'    => ['required', 'integer'],
                'class_id'   => ['required', 'integer'],
                'teacher_id' => ['nullable', 'integer'],
            ]);

            $query = AttendanceRecord::with(['student.user', 'classSession'])
                ->whereHas('classSession', function ($q) use ($validated) {
                    $q->where('term_id', $validated['term_id'])
                      ->where('class_id', $validated['class_id']);
                    
                    if (!empty($validated['teacher_id'])) {
                        $q->where('teacher_id', $validated['teacher_id']);
                    }
                })
                ->whereBetween('attendance_date', [$validated['start_date'], $validated['end_date']]);

            $records = $query->get();

            if ($records->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $records
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, CreateAttendanceRecord $action)
    {
        try {
            $validated = $request->validate([
                'class_session_id'     => ['required', 'integer', 'exists:class_sessions,id'],
                'date'                 => ['required', 'date', 'before_or_equal:today'],
                'records'              => ['required', 'array', 'min:1'],
                'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
                'records.*.status'     => ['required', Rule::in(['present', 'absent', 'permission'])],
                'records.*.comment'    => ['nullable', 'string'],
            ]);

            $result = $action->execute($validated);

            return response()->json($result, 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {

            $record = AttendanceRecord::with([
                'student',
                'classSession',
                'recordedBy'
            ])
                ->find($id);

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $record
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UpdateAttendanceRecord $action)
    {
        try {
            $validated = $request->validate([
                'class_session_id'     => ['required', 'integer', 'exists:class_sessions,id'],
                'date'                 => ['required', 'date', 'before_or_equal:today'],
                'records'              => ['required', 'array', 'min:1'],
                'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
                'records.*.status'     => ['required', Rule::in(['present', 'absent', 'permission'])],
                'records.*.comment'    => ['nullable', 'string'],
            ]);

            $result = $action->execute($validated);

            return response()->json($result, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, DeleteAttendanceRecord $action)
    {
        $validated = $request->validate([
            'class_session_id' => ['required', 'integer', 'exists:class_sessions,id'],
            'date'             => ['required', 'date', 'before_or_equal:today'],
            'student_ids'      => ['required', 'array', 'min:1'],
            'student_ids.*'    => ['integer', 'exists:students,id'],
        ]);

        $result = $action->execute($validated);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 500);
        }

        return response()->json($result, 200);
    }
}
