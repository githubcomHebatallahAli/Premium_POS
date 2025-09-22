<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DamageProductRequest;
use App\Http\Resources\Admin\DamageProductResource;
use App\Http\Resources\Admin\ShowAllDamageProductResource;
use App\Models\DamageProduct;
use App\Models\ShipmentProduct;
use App\Traits\ManagesModelsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DamageProductController extends Controller
{
    use ManagesModelsTrait;
        public function showAll(Request $request)
    {
        // $this->authorize('showAll',DamageProduct::class);
        $query  = DamageProduct::with(['product','variant','shipment', 'product.category', 'product.brand']);
         if ($request->filled('brand_id')) {
    $query->whereHas('product', function ($q) use ($request) {
        $q->where('brand_id', $request->brand_id);
    });
}

if ($request->filled('category_id')) {
    $query->whereHas('product', function ($q) use ($request) {
        $q->where('category_id', $request->category_id);
    });
}

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $DamageProduct = $query->orderBy('created_at', 'desc')
        ->paginate(10);

                  return response()->json([
                      'data' =>  ShowAllDamageProductResource::collection($DamageProduct),
                      'pagination' => [
                        'total' => $DamageProduct->total(),
                        'count' => $DamageProduct->count(),
                        'per_page' => $DamageProduct->perPage(),
                        'current_page' => $DamageProduct->currentPage(),
                        'total_pages' => $DamageProduct->lastPage(),
                        'next_page_url' => $DamageProduct->nextPageUrl(),
                        'prev_page_url' => $DamageProduct->previousPageUrl(),
                    ],
                      'message' => "Show All DamageProduct  With Products."
                  ]);
    }

    public function showAllDamageProduct()
    {
        // $this->authorize('showAllCat',DamageProduct::class);
        $DamageProduct = DamageProduct::with(['product','variant','shipment', 'product.category', 'product.brand'])
        ->get();
             return response()->json([
                'data' => ShowAllDamageProductResource::collection($DamageProduct),
                'message' => "Show All DamageProduct  With Products."
                  ]);
    }


    public function create(DamageProductRequest $request)
    {
        // $this->authorize('create',DamageProduct::class);
    return DB::transaction(function () use ($request) {
        // 1- إنشاء سجل التالف
        $DamageProduct = DamageProduct::create([
            "product_id" => $request->product_id,
            "product_variant_id" => $request->product_variant_id,
            "shipment_product_id" => $request->shipment_product_id,
            "quantity" => $request->quantity,
            "reason" => $request->reason,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
        ]);

        $shipmentProduct = ShipmentProduct::where('product_id', $request->product_id)
            ->where('shipment_id', $request->shipment_id)
            ->when($request->product_variant_id, function ($query) use ($request) {
                return $query->where('product_variant_id', $request->product_variant_id);
            })
            ->lockForUpdate() 
            ->first();

        if (!$shipmentProduct) {
            throw new \Exception("ShipmentProduct not found.");
        }

        if ($shipmentProduct->remainingQuantity < $request->quantity) {
            throw new \Exception("الكمية المتبقية أقل من الكمية المطلوبة للتالف.");
        }

        $shipmentProduct->decrement('remainingQuantity', $request->quantity);

        return response()->json([
            'data' => new DamageProductResource($DamageProduct),
            'message' => "DamageProduct Created Successfully."
        ]);
    });
}
        

        public function edit(string $id)
        {
            // $this->authorize('manage_users');
        $DamageProduct = DamageProduct::with(['product', 'variant', 'shipment', 'product.category', 'product.brand'])
        ->find($id);

            if (!$DamageProduct) {
                return response()->json([
                    'message' => "DamageProduct not found."
                ], 404);
            }

            // $this->authorize('edit',$DamageProduct);

            return response()->json([
                'data' => new DamageProductResource($DamageProduct),
                'message' => "Edit DamageProduct With Products By ID Successfully."
            ]);
        }

public function update(DamageProductRequest $request, string $id)
{
    $this->authorize('manage_users');

    return DB::transaction(function () use ($request, $id) {
        $DamageProduct = DamageProduct::findOrFail($id);

        if (!$DamageProduct) {
            return response()->json([
                'message' => "DamageProduct not found."
            ], 404);
        }

        $shipmentProduct = ShipmentProduct::where('product_id', $DamageProduct->product_id)
            ->where('shipment_id', $DamageProduct->shipment_id)
            ->when($DamageProduct->product_variant_id, function ($query) use ($DamageProduct) {
                return $query->where('product_variant_id', $DamageProduct->product_variant_id);
            })
            ->lockForUpdate()
            ->first();

        if (!$shipmentProduct) {
            throw new \Exception("ShipmentProduct not found.");
        }

        $oldQuantity = $DamageProduct->quantity;
        $newQuantity = $request->quantity;
        $quantityDifference = $newQuantity - $oldQuantity;

        if ($quantityDifference > 0) {
            if ($shipmentProduct->remainingQuantity < $quantityDifference) {
                throw new \Exception("الكمية المتبقية أقل من الكمية المطلوبة للتعديل.");
            }
            $shipmentProduct->decrement('remainingQuantity', $quantityDifference);
        } elseif ($quantityDifference < 0) {
            $shipmentProduct->increment('remainingQuantity', abs($quantityDifference));
        }

      
        $DamageProduct->update([
            "product_id" => $request->product_id,
            "product_variant_id" => $request->product_variant_id,
            "shipment_product_id" => $request->shipment_product_id,
            "quantity" => $newQuantity,
            "reason" => $request->reason,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'data' => new DamageProductResource($DamageProduct),
            'message' => "Update DamageProduct By Id Successfully."
        ]);
    });
}


    public function destroy(string $id){

    return $this->destroyModel(DamageProduct::class, DamageProductResource::class, $id);
    }

    public function showDeleted(){
        $this->authorize('manage_users');
    $DamageProducts=DamageProduct::onlyTrashed()->get();
    return response()->json([
        'data' =>DamageProductResource::collection($DamageProducts),
        'message' => "Show Deleted DamageProducts Successfully."
    ]);
    }

    public function restore(string $id)
    {
       $this->authorize('manage_users');
    $DamageProduct = DamageProduct::withTrashed()->where('id', $id)->first();
    if (!$DamageProduct) {
        return response()->json([
            'message' => "DamageProduct not found."
        ], 404);
    }
    $DamageProduct->restore();
    return response()->json([
        'data' =>new DamageProductResource($DamageProduct),
        'message' => "Restore DamageProduct By Id Successfully."
    ]);
    }

    public function forceDelete(string $id){

        return $this->forceDeleteModel(DamageProduct::class, $id);
    }
    
}
