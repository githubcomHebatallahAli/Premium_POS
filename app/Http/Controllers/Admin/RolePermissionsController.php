<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RolePermissionsRequest;
use App\Models\Role;
use Illuminate\Http\Request;

class RolePermissionsController extends Controller
{
            public function assignRoleToPermissions(RolePermissionsRequest $request)
    {
        $role = Role::find($request->role_id);
        $rolesPermissions = $role->permissions()->attach($request->permissions_id);
        return response()->json([
            "message" => "Permissions assigned to role successfully"
        ]);

    }

    public function revokeRoleFromPermissions(RolePermissionsRequest $request)
{
    $role = Role::find($request->role_id);
    $role->permissions()->detach($request->permissions_id);

    return response()->json([
        "message" => "Permissions revoked from role successfully"
    ]);
}

public function showAllRolesWithPermissions(){

    $rolesWithPermissions = [];

    $roles = Role::with('permissions')->get();

    foreach ($roles as $role) {
        $permissions = $role->permissions->pluck('name')->toArray();

        $rolesWithPermissions[] = [
            'role_name' => $role->name,
            'permissions' => $permissions
        ];
    }
    return response()->json([
        'data' => $rolesWithPermissions,
        'message' => "Show All Roles With Permissions Successfully."
    ]);
}
}
