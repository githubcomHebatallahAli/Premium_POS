<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Http\Resources\Admin\ProductVariantResource;
use App\Models\ProductVariant;
use App\Traits\ManagesModelsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductVariantController extends Controller
{
    use ManagesModelsTrait;
            public function showAll(Request $request)
    {
        // $this->authorize('showAll',ProductVariant::class);
          $this->authorize('manage_users');
      
        $ProductVariant = ProductVariant::with(['category','brand']);
        
    $query = ProductVariant::query();

    if ($request->filled('brand_id')) {
        $query->where('brand_id', $request->brand_id);
    }

    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }
    // if ($request->filled('color')) {
    //     $query->where('color', $request->color);
    // }
    // if ($request->filled('size')) {
    //     $query->where('size', $request->size);
    // }

    // if ($request->filled('clothes')) {
    //     $query->where('clothes', $request->clothes);
    // }

    // if ($request->filled('endDate')) {
    //     $query->where('endDate', $request->endDate);
    // }


        $ProductVariant = $ProductVariant->orderBy('created_at', 'desc')
                           ->paginate(10);

        return response()->json([
            'data' => ShowAllProductVariantResource::collection($ProductVariant),
            'pagination' => [
                'total' => $ProductVariant->total(),
                'count' => $ProductVariant->count(),
                'per_page' => $ProductVariant->perPage(),
                'current_page' => $ProductVariant->currentPage(),
                'total_pages' => $ProductVariant->lastPage(),
                'next_page_url' => $ProductVariant->nextPageUrl(),
                'prev_page_url' => $ProductVariant->previousPageUrl(),
            ],
            'message' => "Show All ProductVariants."
        ]);
    }

    public function showAllProductVariant()
    {
          $this->authorize('manage_users');
        // $this->authorize('showAllProductVariant',ProductVariant::class);

        $ProductVariant = ProductVariant::with(['category','brand'])->get();

        return response()->json([
            'data' => ShowAllProductVariantResource::collection($ProductVariant),
            'message' => "Show All ProductVariants."
        ]);
    }

    public function showProductVariantLessThan5()
{
    // $this->authorize('showProductVariantLessThan5', ProductVariant::class);
$this->authorize('manage_users');
    $ProductVariants = ProductVariant::with(['category', 'brand'])
                        ->where('quantity', '<=', 5)
                        ->get();

    return response()->json([
        'data' => ShowAllProductVariantResource::collection($ProductVariants),
        'message' => "Show All ProductVariants with quantity <= 5."
    ]);
}


    public function create(ProductVariantRequest $request)
    {
        $this->authorize('manage_users');
        // $this->authorize('create',ProductVariant::class);
        $formattedSellingPrice = number_format($request->sellingPrice, 2, '.', '');
      
           $ProductVariant =ProductVariant::create ([
                "product_id" => $request->product_id,
               
                "sellingPrice" => $formattedSellingPrice,
                'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
                'color' => $request->color,
                'size' => $request->size,
                'clothes' => $request->clothes,
                'barcode' => $request->barcode,
                'notes' => $request->notes,
            ]);

            if ($request->hasFile('images')) {
                $imagesPath = $request->file('images')->store(ProductVariant::storageFolder);
                $ProductVariant->images = $imagesPath;
            }

           $ProductVariant->save();
           return response()->json([
            'data' =>new ProductVariantResource($ProductVariant),
            'message' => "ProductVariant Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
            $this->authorize('manage_users');
            $ProductVariant = ProductVariant::with(['category','brand'])->find($id);

            if (!$ProductVariant) {
                return response()->json([
                    'message' => "ProductVariant not found."
                ], 404);
            }

            // $this->authorize('edit',$ProductVariant);

            return response()->json([
                'data' => new ProductVariantResource($ProductVariant),
                'message' => "Edit ProductVariant By ID Successfully."
            ]);
        }

        public function update(ProductVariantRequest $request, string $id)
        {
            $this->authorize('manage_users');
            $formattedSellingPrice = number_format($request->sellingPrice, 2, '.', '');
           
           $ProductVariant =ProductVariant::findOrFail($id);
           if (!$ProductVariant) {
            return response()->json([
                'message' => "ProductVariant not found."
            ], 404);
        }

        // $this->authorize('update',$ProductVariant);
           $ProductVariant->update([
                "product_id" => $request->product_id,
                "sellingPrice" => $formattedSellingPrice,
                'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
                'color' => $request->color,
                'size' => $request->size,
                'clothes' => $request->clothes,
                'barcode' => $request->barcode,
                'notes' => $request->notes,
            ]);

            if ($request->hasFile('images')) {
                if ($ProductVariant->images) {
                    Storage::disk('public')->delete( $ProductVariant->images);
                }
                $imagesPath = $request->file('images')->store('ProductVariants', 'public');
                 $ProductVariant->images = $imagesPath;
            }
            $ProductVariant->save();

           return response()->json([
            'data' =>new ProductVariantResource($ProductVariant),
            'message' => " Update ProductVariant By Id Successfully."
        ]);
    }

    public function destroy(string $id){

    return $this->destroyModel(ProductVariant::class, ProductVariantResource::class, $id);
    }

    public function showDeleted(){
        $this->authorize('manage_users');
    $ProductVariants=ProductVariant::onlyTrashed()->get();
    return response()->json([
        'data' =>ProductVariantResource::collection($ProductVariants),
        'message' => "Show Deleted ProductVariants Successfully."
    ]);
    }

    public function restore(string $id)
    {
       $this->authorize('manage_users');
    $ProductVariant = ProductVariant::withTrashed()->where('id', $id)->first();
    if (!$ProductVariant) {
        return response()->json([
            'message' => "ProductVariant not found."
        ], 404);
    }
    $ProductVariant->restore();
    return response()->json([
        'data' =>new ProductVariantResource($ProductVariant),
        'message' => "Restore ProductVariant By Id Successfully."
    ]);
    }

    public function forceDelete(string $id){

        return $this->forceDeleteModel(ProductVariant::class, $id);
    }
}
