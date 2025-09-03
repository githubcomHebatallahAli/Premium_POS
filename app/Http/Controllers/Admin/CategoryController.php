<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Requests\Admin\MainRequest;
use App\Http\Resources\Admin\CategoryProductResource;
use App\Http\Resources\Admin\CategoryResource;
use App\Http\Resources\Admin\MainResource;
use App\Models\Category;
use App\Traits\ManagesModelsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    use ManagesModelsTrait;
    public function showAll(Request $request)
    {
        // $this->authorize('showAll',Category::class);
        $searchTerm = $request->input('search', '');

        $category = Category::withCount('products')
        ->where('name', 'like', '%' . $searchTerm . '%')
        ->orderBy('created_at', 'desc')
        ->paginate(10);

                  return response()->json([
                      'data' =>  MainResource::collection($category),
                      'pagination' => [
                        'total' => $category->total(),
                        'count' => $category->count(),
                        'per_page' => $category->perPage(),
                        'current_page' => $category->currentPage(),
                        'total_pages' => $category->lastPage(),
                        'next_page_url' => $category->nextPageUrl(),
                        'prev_page_url' => $category->previousPageUrl(),
                    ],
                      'message' => "Show All Category  With Products."
                  ]);
    }

    public function showAllCat()
    {
        // $this->authorize('showAllCat',Category::class);

        $category = Category::withCount('products')->get();

                  return response()->json([
                      'data' =>  MainResource::collection($category),
                      'message' => "Show All Category  With Products."
                  ]);
    }


    public function create(MainRequest $request)
    {
        // $this->authorize('create',Category::class);
           $Category =Category::create ([
                "name" => $request->name,
                'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);

           return response()->json([
            'data' =>new mainResource($Category),
            'message' => "Category Created Successfully."
        ]);
        }


        public function edit(string $id)
        {
            // $this->authorize('manage_users');
        $category = Category::withCount('products')
        ->with('products')
        ->find($id);

            if (!$category) {
                return response()->json([
                    'message' => "Category not found."
                ], 404);
            }

            // $this->authorize('edit',$category);
            return response()->json([
                'data' => new CategoryProductResource($category),
                'message' => "Edit Category With Products By ID Successfully."
            ]);
        }

        public function update(MainRequest $request, string $id)
        {
            $this->authorize('manage_users');
           $Category =Category::findOrFail($id);

           if (!$Category) {
            return response()->json([
                'message' => "Category not found."
            ], 404);
        }
        // $this->authorize('update',$Category);
           $Category->update([
            "name" => $request->name,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);

           $Category->save();
           return response()->json([
            'data' =>new MainResource($Category),
            'message' => " Update Category By Id Successfully."
        ]);
    }

    public function destroy(string $id){

    return $this->destroyModel(Category::class, MainResource::class, $id);
    }

    public function showDeleted(){
        $this->authorize('manage_users');
    $Categorys=Category::onlyTrashed()->get();
    return response()->json([
        'data' =>MainResource::collection($Categorys),
        'message' => "Show Deleted Categorys Successfully."
    ]);
    }

    public function restore(string $id)
    {
       $this->authorize('manage_users');
    $Category = Category::withTrashed()->where('id', $id)->first();
    if (!$Category) {
        return response()->json([
            'message' => "Category not found."
        ], 404);
    }
    $Category->restore();
    return response()->json([
        'data' =>new MainResource($Category),
        'message' => "Restore Category By Id Successfully."
    ]);
    }

    public function forceDelete(string $id){

        return $this->forceDeleteModel(Category::class, $id);
    }

    // public function view(string $id)
    // {
    //     $this->authorize('manage_users');
    //     $Category =Category::findOrFail($id);

    //     if (!$Category) {
    //      return response()->json([
    //          'message' => "Category not found."
    //      ], 404);
    //  }

    //     $Category->update(['status' => 'view']);

    //     return response()->json([
    //         'data' => new CategoryResource($Category),
    //         'message' => 'Category has been view.'
    //     ]);
    // }

    // public function notView(string $id)
    // {
    //     $this->authorize('manage_users');
    //     $Category =Category::findOrFail($id);

    //     if (!$Category) {
    //      return response()->json([
    //          'message' => "Category not found."
    //      ], 404);
    //  }

    //     $Category->update(['status' => 'notView']);

    //     return response()->json([
    //         'data' => new CategoryResource($Category),
    //         'message' => 'Category has been delivery.'
    //     ]);
    // }

    }


