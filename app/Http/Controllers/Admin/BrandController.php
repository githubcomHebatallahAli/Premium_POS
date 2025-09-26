<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MainRequest;
use App\Http\Resources\Admin\CategoryProductResource;
use App\Http\Resources\Admin\MainResource;
use App\Models\Brand;
use App\Traits\ManagesModelsTrait;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use ManagesModelsTrait;
    public function showAll(Request $request)
    {
        // $this->authorize('showAll',Brand::class);
        $searchTerm = $request->input('search', '');

        $Brand = Brand::withCount('products')
        ->where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc')
        ->paginate(10);

                  return response()->json([
                      'data' =>  MainResource::collection($Brand),
                      'pagination' => [
                        'total' => $Brand->total(),
                        'count' => $Brand->count(),
                        'per_page' => $Brand->perPage(),
                        'current_page' => $Brand->currentPage(),
                        'total_pages' => $Brand->lastPage(),
                        'next_page_url' => $Brand->nextPageUrl(),
                        'prev_page_url' => $Brand->previousPageUrl(),
                    ],
                      'message' => "Show All Brand  With Products."
                  ]);
    }

    public function showAllBrand()
    {
        // $this->authorize('showAllCat',Brand::class);

        $Brand = Brand::withCount('products')->get();

                  return response()->json([
                      'data' =>  MainResource::collection($Brand),
                      'message' => "Show All Brand  With Products."
                  ]);
    }


    public function create(MainRequest $request)
    {
        // $this->authorize('create',Brand::class);
           $Brand =Brand::create ([
                "name" => $request->name,
                'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);

           return response()->json([
            'data' =>new MainResource($Brand),
            'message' => "Brand Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
            // $this->authorize('manage_users');
        $Brand = Brand::withCount('products')
        ->with('products')
        ->find($id);

            if (!$Brand) {
                return response()->json([
                    'message' => "Brand not found."
                ], 404);
            }

            // $this->authorize('edit',$Brand);

            return response()->json([
                'data' => new CategoryProductResource($Brand),
                'message' => "Edit Brand With Products By ID Successfully."
            ]);
        }

        public function update(MainRequest $request, string $id)
        {
            // $this->authorize('manage_users');
           $Brand =Brand::findOrFail($id);

           if (!$Brand) {
            return response()->json([
                'message' => "Brand not found."
            ], 404);
        }
        // $this->authorize('update',$Brand);
           $Brand->update([
            "name" => $request->name,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);

           $Brand->save();
           return response()->json([
            'data' =>new MainResource($Brand),
            'message' => " Update Brand By Id Successfully."
        ]);
    }

    public function destroy(string $id){

    return $this->destroyModel(Brand::class, MainResource::class, $id);
    }

    public function showDeleted(){
        // $this->authorize('manage_users');
    $Brands=Brand::onlyTrashed()->get();
    return response()->json([
        'data' =>MainResource::collection($Brands),
        'message' => "Show Deleted Brands Successfully."
    ]);
    }

    public function restore(string $id)
    {
    //    $this->authorize('manage_users');
    $Brand = Brand::withTrashed()->where('id', $id)->first();
    if (!$Brand) {
        return response()->json([
            'message' => "Brand not found."
        ], 404);
    }
    $Brand->restore();
    return response()->json([
        'data' =>new MainResource($Brand),
        'message' => "Restore Brand By Id Successfully."
    ]);
    }

    public function forceDelete(string $id){

        return $this->forceDeleteModel(Brand::class, $id);
    }
}
