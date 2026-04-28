<?php

namespace App\Actions\Teacher;

use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTeacher
{
    public function execute(Request $request): Teacher
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'teacher_code' => ['nullable', 'string', 'unique:teachers,teacher_code'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Ensure the user has the 'teacher' role
            $teacherRole = Role::where('key', 'teacher')->first();
            if ($teacherRole) {
                UserRole::firstOrCreate([
                    'user_id' => $validated['user_id'],
                    'role_id' => $teacherRole->id,
                ]);
            }

            // 2. Create the teacher record
            $teacherCode = $validated['teacher_code'] ?? 'TCH' . str_pad($validated['user_id'], 4, '0', STR_PAD_LEFT);
            
            return Teacher::updateOrCreate(
                ['user_id' => $validated['user_id']],
                [
                    'teacher_code' => $teacherCode,
                    'status' => $validated['status'] ?? 'active',
                ]
            );
        });
    }
}
