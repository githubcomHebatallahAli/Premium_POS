<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Traits\ManagesModelsTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Http\Resources\Admin\RoleResource;

class RoleController extends Controller
{
    use ManagesModelsTrait;
    public function showAll()
    {
        $this->authorize('manage_users');

        $Roles = Role::get();
        return response()->json([
            'data' => RoleResource::collection($Roles),
            'message' => "Show All Roles Successfully."
        ]);
    }


    public function create(RoleRequest $request)
    {
        $this->authorize('manage_users');

           $Role =Role::create ([
                "name" => $request->name
            ]);
           $Role->save();
           return response()->json([
            'data' =>new RoleResource($Role),
            'message' => "Role Created Successfully."
        ]);
        }


    public function edit(string $id)
    {
        $this->authorize('manage_users');
        $Role = Role::find($id);

        if (!$Role) {
            return response()->json([
                'message' => "Role not found."
            ], 404);
        }

        return response()->json([
            'data' =>new RoleResource($Role),
            'message' => "Edit Role By ID Successfully."
        ]);
    }



    public function update(RoleRequest $request, string $id)
    {
        $this->authorize('manage_users');
       $Role =Role::findOrFail($id);

       if (!$Role) {
        return response()->json([
            'message' => "Role not found."
        ], 404);
    }
       $Role->update([
        "name" => $request->name
        ]);

       $Role->save();
       return response()->json([
        'data' =>new RoleResource($Role),
        'message' => " Update Role By Id Successfully."
    ]);

  }

  public function destroy(string $id)
  {
      return $this->destroyModel(Role::class, RoleResource::class, $id);
  }

  public function showDeleted()
  {
    $this->authorize('manage_users');
$Roles=Role::onlyTrashed()->get();
return response()->json([
    'data' =>RoleResource::collection($Roles),
    'message' => "Show Deleted Roles Successfully."
]);

}

public function restore(string $id)
{
   $this->authorize('manage_users');
$Role = Role::withTrashed()->where('id', $id)->first();
if (!$Role) {
    return response()->json([
        'message' => "Role not found."
    ], 404);
}
$Role->restore();
return response()->json([
    'data' =>new RoleResource($Role),
    'message' => "Restore Role By Id Successfully."
]);
}

  public function forceDelete(string $id)
  {
      return $this->forceDeleteModel(Role::class, $id);
  }




}
