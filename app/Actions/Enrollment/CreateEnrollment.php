<?php

namespace App\Actions\Enrollment;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\StudentStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreateEnrollment
{
    public function create(Request $request): Enrollment
    {
        // Allow user_id as a fallback if student_id is not provided
        if (!$request->has('student_id') && $request->has('user_id')) {
            $student = Student::where('user_id', $request->user_id)->first();
            if ($student) {
                $request->merge(['student_id' => $student->id]);
            }
        }

        $validated = $request->validate([
            'class_id'    => ['required', 'integer', 'exists:classes,id'],
            'student_id'  => ['required', 'integer', 'exists:students,id'],
            'enrolled_on' => ['nullable', 'date'],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        if ($student->status !== StudentStatus::ACTIVE) {
            throw ValidationException::withMessages([
                'student_id' => ['Cannot enroll an inactive or suspended student.'],
            ]);
        }

        $exists = Enrollment::where('class_id', $validated['class_id'])
            ->where('student_id', $validated['student_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'student_id' => ['This student is already enrolled in this class.'],
            ]);
        }

        $class = Classes::findOrFail($validated['class_id']);
        $validated['grade_level_id'] = $class->grade_level_id;
        $validated['enrolled_on']    = $validated['enrolled_on'] ?? now()->toDateString();

        return Enrollment::create($validated);
    }
}
