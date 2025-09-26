<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RolePermissionsRequest;
use App\Models\Role;
use Illuminate\Http\Request;

class RolePermissionsController extends Controller
{
//             public function assignRoleToPermissions(RolePermissionsRequest $request)
//     {
//         $this->authorize('manage_users');
//         $role = Role::find($request->role_id);
//         $rolesPermissions = $role->permissions()->attach($request->permissions_id);
//         return response()->json([
//             "message" => "Permissions assigned to role successfully"
//         ]);

//     }

//     public function revokeRoleFromPermissions(RolePermissionsRequest $request)
// {
//     $this->authorize('manage_users');
//     $role = Role::find($request->role_id);
//     $role->permissions()->detach($request->permissions_id);

//     return response()->json([
//         "message" => "Permissions revoked from role successfully"
//     ]);
// }

public function assignRoleToPermissions(RolePermissionsRequest $request, $roleId)
{
    $this->authorize('manage_users');

    $role = Role::findOrFail($roleId);
    $role->permissions()->attach($request->permissions_id);

    return response()->json([
        "message" => "Permissions assigned to role successfully"
    ]);
}

public function revokeRoleFromPermissions(RolePermissionsRequest $request, $roleId)
{
    $this->authorize('manage_users');

    $role = Role::findOrFail($roleId);
    $role->permissions()->detach($request->permissions_id);

    return response()->json([
        "message" => "Permissions revoked from role successfully"
    ]);
}


public function showAllRolesWithPermissions(){
    $this->authorize('manage_users');

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

public function showRoleWithPermissions($id)
{
    $this->authorize('manage_users');

    $role = Role::with('permissions')->findOrFail($id);

    return response()->json([
        'data' => [
            'role_name'   => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray(),
        ],
        'message' => "Show Role With Permissions Successfully."
    ]);
}

}
