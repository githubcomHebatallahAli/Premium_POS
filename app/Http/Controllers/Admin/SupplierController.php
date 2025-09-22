<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Http\Requests\Admin\SupplierRequest;
use App\Http\Resources\Admin\SupplierResource;
use App\Models\Supplier;
use App\Traits\ManagesModelsTrait;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use ManagesModelsTrait;
        public function showAll(Request $request)
    {
        $this->authorize('manage_users');
        $searchTerm = $request->input('search', '');

        $Supplier = Supplier::where('supplierName', 'like', '%' . $searchTerm . '%')
        
        ->orderBy('created_at', 'desc')
        ->paginate(10);

                  return response()->json([
                      'data' =>  SupplierResource::collection($Supplier),
                      'pagination' => [
                        'total' => $Supplier->total(),
                        'count' => $Supplier->count(),
                        'per_page' => $Supplier->perPage(),
                        'current_page' => $Supplier->currentPage(),
                        'total_pages' => $Supplier->lastPage(),
                        'next_page_url' => $Supplier->nextPageUrl(),
                        'prev_page_url' => $Supplier->previousPageUrl(),
                    ],
                      'message' => "Show All Suppliers Successfully."
                  ]);
    }

        public function showAllWithoutPaginate(Request $request)
    {
        $this->authorize('manage_users');

       $Supplier = Supplier::orderBy('created_at', 'desc')
        ->get();
                  return response()->json([
                      'data' =>  SupplierResource::collection($Supplier),
                      'message' => "Show All Suppliers Successfully."
                  ]);
    }


    public function create(SupplierRequest $request)
    {
         $this->authorize('manage_users');
        // $this->authorize('create',Supplier::class);
           $Supplier =Supplier::create ([
                "supplierName" => $request->supplierName,
                "phoNum" => $request-> phoNum,
                "place" => $request-> place,
                'companyName' => $request->companyName,
                'description' => $request->description,
                'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);
          
           return response()->json([
            'data' =>new SupplierResource($Supplier),
            'message' => "Supplier Created Successfully."
        ]);
        }


    public function edit(string $id)
    {
        $this->authorize('manage_users');
        $Supplier = Supplier::find($id);

        if (!$Supplier) {
            return response()->json([
                'message' => "Supplier not found."
            ], 404);
        }

        // $this->authorize('edit',$Supplier);

        return response()->json([
            'data' =>new SupplierResource($Supplier),
            'message' => "Edit Supplier By ID Successfully."
        ]);
    }



    public function update(SupplierRequest $request, string $id)
    {
         $this->authorize('manage_users');
       $Supplier =Supplier::findOrFail($id);

       if (!$Supplier) {
        return response()->json([
            'message' => "Supplier not found."
        ], 404);
    }
    
    // $this->authorize('update',$Supplier);
       $Supplier->update([
        "supplierName" => $request->supplierName,
        "phoNum" => $request-> phoNum,
        "place" => $request-> place,
        'companyName' => $request->companyName,
        'description' => $request->description,
        'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
        ]);

       $Supplier->save();
       return response()->json([
        'data' =>new SupplierResource($Supplier),
        'message' => " Update Supplier By Id Successfully."
    ]);

  }

  public function destroy(string $id)
  {
      return $this->destroyModel(Supplier::class, SupplierResource::class, $id);
  }

  public function showDeleted()
  {
    $this->authorize('manage_users');
$Suppliers=Supplier::onlyTrashed()->get();
return response()->json([
    'data' =>SupplierResource::collection($Suppliers),
    'message' => "Show Deleted Suppliers Successfully."
]);

}

public function restore(string $id)
{
   $this->authorize('manage_users');
$Supplier = Supplier::withTrashed()->where('id', $id)->first();
if (!$Supplier) {
    return response()->json([
        'message' => "Supplier not found."
    ], 404);
}
$Supplier->restore();
return response()->json([
    'data' =>new SupplierResource($Supplier),
    'message' => "Restore Supplier By Id Successfully."
]);
}

  public function forceDelete(string $id)
  {
      return $this->forceDeleteModel(Supplier::class, $id);
  }


}
