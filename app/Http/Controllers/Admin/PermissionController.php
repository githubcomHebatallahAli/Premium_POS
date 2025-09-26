<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Http\Resources\Admin\RoleResource;
use App\Models\Permission;
use App\Traits\ManagesModelsTrait;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
        use ManagesModelsTrait;
    public function showAll()
    {
        $this->authorize('manage_users');

        $Permissions = Permission::get();
        return response()->json([
            'data' => RoleResource::collection($Permissions),
            'message' => "Show All Permissions Successfully."
        ]);
    }


    public function create(RoleRequest $request)
    {
        $this->authorize('manage_users');

           $Permission =Permission::create ([
                "name" => $request->name
            ]);
           $Permission->save();
           return response()->json([
            'data' =>new RoleResource($Permission),
            'message' => "Permission Created Successfully."
        ]);
        }


    public function edit(string $id)
    {
        $this->authorize('manage_users');
        $Permission = Permission::find($id);

        if (!$Permission) {
            return response()->json([
                'message' => "Permission not found."
            ], 404);
        }
        return response()->json([
            'data' =>new RoleResource($Permission),
            'message' => "Edit Permission By ID Successfully."
        ]);
    }

    public function update(RoleRequest $request, string $id)
    {
        $this->authorize('manage_users');
       $Permission =Permission::findOrFail($id);

       if (!$Permission) {
        return response()->json([
            'message' => "Permission not found."
        ], 404);
    }
       $Permission->update([
        "name" => $request->name
        ]);

       $Permission->save();
       return response()->json([
        'data' =>new RoleResource($Permission),
        'message' => " Update Permission By Id Successfully."
    ]);

  }

  public function destroy(string $id)
  {
      return $this->destroyModel(Permission::class, RoleResource::class, $id);
  }

  public function showDeleted()
  {
    $this->authorize('manage_users');
$Permissions=Permission::onlyTrashed()->get();
return response()->json([
    'data' =>RoleResource::collection($Permissions),
    'message' => "Show Deleted Permissions Successfully."
]);
}

public function restore(string $id)
{
   $this->authorize('manage_users');
$Permission = Permission::withTrashed()->where('id', $id)->first();
if (!$Permission) {
    return response()->json([
        'message' => "Permission not found."
    ], 404);
}
$Permission->restore();
return response()->json([
    'data' =>new RoleResource($Permission),
    'message' => "Restore Permission By Id Successfully."
]);
}

  public function forceDelete(string $id)
  {
      return $this->forceDeleteModel(Permission::class, $id);
  }
}
