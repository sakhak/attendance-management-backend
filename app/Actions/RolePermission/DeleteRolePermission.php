<?php

namespace App\Actions\RolePermission;

use App\Models\RolePermission;
use Illuminate\Http\Request;

class DeleteRolePermission
{
    public function execute(Request $request)
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'permission_id' => ['required', 'array'],
            'permission_id.*' => ['exists:permissions,id'],
        ]);

        $roleId = $validated['role_id'];
        $deletedCount = 0;
        $notFound = [];

        foreach ($validated['permission_id'] as $pid) {
            $rolePermission = RolePermission::where('role_id', $roleId)
                ->where('permission_id', $pid)
                ->first();

            if (!$rolePermission) {
                $notFound[] = $pid;
                continue;
            }

            $rolePermission->delete();
            $deletedCount++;
        }

        if ($deletedCount === 0) {
            return response()->json([
                'data' => null,
                'message' => 'No permissions were deleted.',
                'not_found_permissions' => $notFound,
            ], 404);
        }

        return response()->json([
            'data' => [
                'role_id' => $roleId,
                'deleted_count' => $deletedCount,
                'not_found_permissions' => $notFound,
            ],
            'message' => 'Role permissions deleted successfully.',
        ], 200);
    }
}
