<?php

namespace App\Actions\RolePermission;

use App\Models\RolePermission;
use Illuminate\Http\Request;

class UpdateRolePermissions
{
    public function execute(Request $request)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'permission_id' => ['required', 'array'],
            'permission_id.*' => ['exists:permissions,id'],
        ]);

        $roleId = $validated['role_id'];
        $successCount = 0;
        $skipped = [];
        $rolePermissions = [];

        foreach ($validated['permission_id'] as $pid) {
            $exists = RolePermission::where('role_id', $roleId)
                ->where('permission_id', $pid)
                ->exists();
            if ($exists) {
                $skipped[] = $pid;
                continue;
            }

            $rolePermissions[] = RolePermission::create([
                'role_id' => $roleId,
                'permission_id' => $pid,
            ]);

            $successCount++;
        }

        if ($successCount === 0) {
            return response()->json([
                'data' => null,
                'message' => 'No permissions were updated (all already assigned).',
                'skipped_permissions' => $skipped,
            ], 409);
        }

        return response()->json([
            'data' => [
                'role_id' => $roleId,
                'permissions' => $rolePermissions,
                'updated_count' => $successCount,
                'skipped_permissions' => $skipped,
            ],
            'message' => 'Role permissions updated successfully.',
        ], 200);
    }
}
