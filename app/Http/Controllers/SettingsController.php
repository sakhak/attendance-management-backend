<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRuleSetting;
use App\Models\Classes;
use App\Models\GradeLevel;
use App\Models\SchoolSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    private const SCHOOL_DEFAULTS = [
        'school_name' => 'SETEC Institute',
        'address' => 'Phnom Penh',
        'city' => 'Phnom Penh',
        'state_province' => 'Phnom Penh',
        'postal_code' => '62704',
        'country' => 'Cambodia',
        'academic_year' => '2025-2026',
        'term_semester' => 'SU10',
    ];

    private const ATTENDANCE_RULE_DEFAULTS = [
        'late_threshold_minutes' => 15,
        'absent_threshold_minutes' => 45,
        'exclude_weekends' => true,
        'auto_exclude_public_holidays' => true,
    ];

    private const DAY_MAP = [
        'mon' => 'Mon',
        'monday' => 'Mon',
        'tue' => 'Tue',
        'tuesday' => 'Tue',
        'wed' => 'Wed',
        'wednesday' => 'Wed',
        'thu' => 'Thu',
        'thursday' => 'Thu',
        'fri' => 'Fri',
        'friday' => 'Fri',
        'sat' => 'Sat',
        'saturday' => 'Sat',
        'sun' => 'Sun',
        'sunday' => 'Sun',
    ];

    public function showSchool(): JsonResponse
    {
        return response()->json($this->schoolSetting()->only(array_keys(self::SCHOOL_DEFAULTS)));
    }

    public function updateSchool(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state_province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:255'],
            'academic_year' => ['nullable', 'string', 'max:50'],
            'term_semester' => ['nullable', 'string', 'max:50'],
        ]);

        $setting = $this->schoolSetting();
        $setting->update($validated);

        return response()->json($setting->fresh()->only(array_keys(self::SCHOOL_DEFAULTS)));
    }

    public function listClasses(): JsonResponse
    {
        $classes = Classes::query()
            ->with(['teachers.user', 'classSessions'])
            ->withCount('enrollments')
            ->orderBy('name')
            ->get()
            ->map(fn (Classes $class) => $this->classPayload($class))
            ->values();

        return response()->json($classes);
    }

    public function storeClass(Request $request): JsonResponse
    {
        $validated = $this->validateClass($request);

        $class = DB::transaction(function () use ($validated) {
            $class = Classes::create([
                'name' => $validated['name'],
                'grade_level_id' => $validated['grade_level_id'] ?? $this->defaultGradeLevel()->id,
                'schedule_days' => $this->normalizeDays($validated['schedule_days'] ?? []),
                'settings_teacher_name' => $validated['teacher_name'] ?? null,
            ]);

            if (!empty($validated['teacher_id'])) {
                $class->teachers()->sync([$validated['teacher_id']]);
            }

            return $class;
        });

        return response()->json($this->classPayload($this->loadClass($class->id)), 201);
    }

    public function updateClass(Request $request, Classes $class): JsonResponse
    {
        $validated = $this->validateClass($request, $class->id, true);

        DB::transaction(function () use ($request, $validated, $class) {
            $updates = [];

            foreach (['name', 'grade_level_id'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $updates[$field] = $validated[$field];
                }
            }

            if (array_key_exists('schedule_days', $validated)) {
                $updates['schedule_days'] = $this->normalizeDays($validated['schedule_days']);
            }

            if (array_key_exists('teacher_name', $validated)) {
                $updates['settings_teacher_name'] = $validated['teacher_name'];
            }

            if ($updates !== []) {
                $class->update($updates);
            }

            if ($request->has('teacher_id')) {
                $validated['teacher_id']
                    ? $class->teachers()->sync([$validated['teacher_id']])
                    : $class->teachers()->detach();
            }
        });

        return response()->json($this->classPayload($this->loadClass($class->id)));
    }

    public function destroyClass(Classes $class): JsonResponse
    {
        $class->delete();

        return response()->json([
            'message' => 'Class deleted successfully',
        ]);
    }

    public function showAttendanceRules(): JsonResponse
    {
        return response()->json($this->attendanceRuleSetting()->only(array_keys(self::ATTENDANCE_RULE_DEFAULTS)));
    }

    public function updateAttendanceRules(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'late_threshold_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
            'absent_threshold_minutes' => ['required', 'integer', 'min:0', 'max:1440', 'gte:late_threshold_minutes'],
            'exclude_weekends' => ['required', 'boolean'],
            'auto_exclude_public_holidays' => ['required', 'boolean'],
        ]);

        $setting = $this->attendanceRuleSetting();
        $setting->update($validated);

        return response()->json($setting->fresh()->only(array_keys(self::ATTENDANCE_RULE_DEFAULTS)));
    }

    private function validateClass(Request $request, ?int $classId = null, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255', Rule::unique('classes', 'name')->ignore($classId)],
            'teacher_id' => ['sometimes', 'nullable', 'integer', Rule::exists('teachers', 'id')],
            'teacher_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'schedule_days' => [$partial ? 'sometimes' : 'required', 'array'],
            'schedule_days.*' => ['string', Rule::in($this->allowedDays())],
            'grade_level_id' => ['sometimes', 'nullable', 'integer', Rule::exists('grade_levels', 'id')],
        ]);
    }

    private function schoolSetting(): SchoolSetting
    {
        return SchoolSetting::firstOrCreate(['id' => 1], self::SCHOOL_DEFAULTS);
    }

    private function attendanceRuleSetting(): AttendanceRuleSetting
    {
        return AttendanceRuleSetting::firstOrCreate(['id' => 1], self::ATTENDANCE_RULE_DEFAULTS);
    }

    private function defaultGradeLevel(): GradeLevel
    {
        return GradeLevel::firstOrCreate(
            ['code' => 'SETTINGS'],
            ['name' => 'Settings Default', 'order_no' => 0, 'is_active' => true]
        );
    }

    private function loadClass(int $id): Classes
    {
        return Classes::with(['teachers.user', 'classSessions'])
            ->withCount('enrollments')
            ->findOrFail($id);
    }

    private function classPayload(Classes $class): array
    {
        $teacher = $class->teachers->first();
        $scheduleDays = $class->schedule_days ?: $class->classSessions
            ->pluck('day_of_week')
            ->map(fn (string $day) => $this->normalizeDay($day))
            ->unique()
            ->values()
            ->all();

        return [
            'id' => $class->id,
            'name' => $class->name,
            'teacher_name' => $teacher?->user?->name ?? $class->settings_teacher_name,
            'schedule_days' => $scheduleDays,
            'student_count' => $class->enrollments_count ?? $class->enrollments()->count(),
        ];
    }

    private function normalizeDays(array $days): array
    {
        return collect($days)
            ->map(fn (string $day) => $this->normalizeDay($day))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeDay(string $day): string
    {
        return self::DAY_MAP[strtolower($day)];
    }

    private function allowedDays(): array
    {
        return array_values(array_unique([
            ...array_keys(self::DAY_MAP),
            ...array_values(self::DAY_MAP),
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ]));
    }
}
