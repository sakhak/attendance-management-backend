<?php

namespace App\Actions\RolePermission;

use App\Models\RolePermission;
use Illuminate\Http\Request;

class CreateRolePermission
{
    public function execute(Request $request)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'permission_id' => ['required', 'array'],
            'permission_id.*' => ['exists:permissions,id'],
        ]);

        $roleId = $validated['role_id'];

        $created = [];
        $skipped = [];

        foreach ($validated['permission_id'] as $pid) {
            $exists = RolePermission::where('role_id', $roleId)
                ->where('permission_id', $pid)
                ->exists();

            if ($exists) {
                $skipped[] = $pid;
                continue;
            }

            $created[] = RolePermission::create([
                'role_id' => $roleId,
                'permission_id' => $pid,
            ]);
        }

        // If nothing created, return 200 instead of 409
        if (count($created) === 0) {
            return response()->json([
                'data' => [
                    'role_id' => $roleId,
                    'created_count' => 0,
                    'created' => [],
                    'skipped_permissions' => $skipped,
                ],
                'message' => 'No new permissions were assigned (all already assigned).',
            ], 200);
        }

        return response()->json([
            'data' => [
                'role_id' => $roleId,
                'created_count' => count($created),
                'created' => $created,
                'skipped_permissions' => $skipped,
            ],
            'message' => 'Permissions assigned to role successfully.',
        ], 201);
    }
}
