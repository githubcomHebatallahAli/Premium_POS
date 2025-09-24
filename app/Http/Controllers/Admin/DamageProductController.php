<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DamageProductRequest;
use App\Http\Requests\Admin\SupplierReturnRequest;
use App\Http\Resources\Admin\DamageProductResource;
use App\Http\Resources\Admin\ShowAllDamageProductResource;
use App\Http\Resources\Admin\SupplierReturnResource;
use App\Models\DamageProduct;
use App\Models\ShipmentProduct;
use App\Models\SupplierReturn;
use App\Traits\ManagesModelsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DamageProductController extends Controller
{
    use ManagesModelsTrait;
// public function showAll(Request $request)
// {
//     $query  = DamageProduct::with(['product','variant','shipmentProduct', 'product.category', 'product.brand']);
//     if ($request->filled('brand_id')) {
//         $query->whereHas('product', function ($q) use ($request) {
//             $q->where('brand_id', $request->brand_id);
//         });
//     }

//     if ($request->filled('category_id')) {
//         $query->whereHas('product', function ($q) use ($request) {
//             $q->where('category_id', $request->category_id);
//         });
//     }

//     if ($request->filled('status')) {
//         $query->where('status', $request->status);
//     }

//       if ($request->filled('search')) {
//         $search = $request->search;
//         $query->whereHas('product', function ($q) use ($search) {
//             $q->where('name', 'like', "%{$search}%");
//         });
//     }

//     if ($request->filled('from_date')) {
//         $query->whereDate('creationDate', '>=', $request->from_date);
//     }

//     if ($request->filled('to_date')) {
//         $query->whereDate('creationDate', '<=', $request->to_date);
//     }

//     $DamageProduct = $query->orderBy('damage_products.created_at', 'desc')->paginate(10);

//     $damageCount = (clone $query)->where('status', 'damage')->sum('quantity');

//     $totalLosses = (clone $query)
//         ->where('status', 'damage')
//         ->join('shipment_products', 'damage_products.shipment_product_id', '=', 'shipment_products.id')
//         ->sum(DB::raw('damage_products.quantity * shipment_products.unitPrice'));

//     return response()->json([
//         'data' => ShowAllDamageProductResource::collection($DamageProduct),
//         'pagination' => [
//             'total' => $DamageProduct->total(),
//             'count' => $DamageProduct->count(),
//             'per_page' => $DamageProduct->perPage(),
//             'current_page' => $DamageProduct->currentPage(),
//             'total_pages' => $DamageProduct->lastPage(),
//             'next_page_url' => $DamageProduct->nextPageUrl(),
//             'prev_page_url' => $DamageProduct->previousPageUrl(),
//         ],
//         'statistics' => [
//             'damage_count' => $damageCount,
//             'total_losses' => number_format($totalLosses, 2),
//         ],
//         'message' => "Show All DamageProduct With Products."
//     ]);
// }

public function showAll(Request $request)
{
    $query  = DamageProduct::with([
        'product',
        'variant',
        'shipmentProduct',
        'product.category',
        'product.brand'
    ]);

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

    if ($request->filled('search')) {
        $search = $request->search;
        $query->whereHas('product', function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        });
    }

    if ($request->filled('from_date')) {
        $query->whereDate('creationDate', '>=', $request->from_date);
    }

    if ($request->filled('to_date')) {
        $query->whereDate('creationDate', '<=', $request->to_date);
    }

    $DamageProduct = $query->orderBy('damage_products.created_at', 'desc')->paginate(10);

    $damageCount = (clone $query)->where('status', 'damage')->sum('quantity');

    // خسائر المنتجات التالفة
    $totalDamageLosses = (clone $query)
        ->where('status', 'damage')
        ->join('shipment_products', 'damage_products.shipment_product_id', '=', 'shipment_products.id')
        ->sum(DB::raw('damage_products.quantity * shipment_products.unitPrice'));

    // خسائر المورد (فرق السعر المسترد)
    $supplierLosses = (clone $query)
        ->where('status', 'return')
        ->join('shipment_products', 'damage_products.shipment_product_id', '=', 'shipment_products.id')
        ->join('supplier_returns', 'damage_products.id', '=', 'supplier_returns.damage_product_id')
        ->sum(DB::raw('(supplier_returns.returned_quantity * shipment_products.unitPrice) - supplier_returns.refund_amount'));

    $totalLosses = $totalDamageLosses + $supplierLosses;

    return response()->json([
        'data' => ShowAllDamageProductResource::collection($DamageProduct),
        'pagination' => [
            'total' => $DamageProduct->total(),
            'count' => $DamageProduct->count(),
            'per_page' => $DamageProduct->perPage(),
            'current_page' => $DamageProduct->currentPage(),
            'total_pages' => $DamageProduct->lastPage(),
            'next_page_url' => $DamageProduct->nextPageUrl(),
            'prev_page_url' => $DamageProduct->previousPageUrl(),
        ],
        'statistics' => [
            'damage_count'    => $damageCount,
            'damage_losses'   => number_format($totalDamageLosses, 2),
            'supplier_losses' => number_format($supplierLosses, 2),
            'total_losses'    => number_format($totalLosses, 2),
        ],
        'message' => "Show All DamageProduct With Products."
    ]);
}



    public function showAllDamageProduct()
    {
        // $this->authorize('showAllCat',DamageProduct::class);
        $DamageProduct = DamageProduct::with(['product','variant','shipmentProduct', 'product.category', 'product.brand'])
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
     
        $DamageProduct = DamageProduct::create([
            "product_id" => $request->product_id,
            "product_variant_id" => $request->product_variant_id,
            "shipment_product_id" => $request->shipment_product_id,
            "quantity" => $request->quantity,
            "reason" => $request->reason,
            'status' => 'damage',
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
        ]);

        // $shipmentProduct = ShipmentProduct::where('product_id', $request->product_id)
        //     ->where('shipment_id', $request->shipment_id)
        //     ->when($request->product_variant_id, function ($query) use ($request) {
        //         return $query->where('product_variant_id', $request->product_variant_id);
        //     })
        //     ->lockForUpdate() 
        //     ->first();

        $shipmentProduct = ShipmentProduct::where('id', $request->shipment_product_id)
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
        $DamageProduct = DamageProduct::with(['product', 'variant', 'shipmentProduct', 'product.category', 'product.brand'])
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

$shipmentProduct = ShipmentProduct::where('id', $request->shipment_product_id)
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
            'status' => $request->status,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'data' => new DamageProductResource($DamageProduct),
            'message' => "Update DamageProduct By Id Successfully."
        ]);
    });
}

public function repaired(Request $request, string $id)
{
    $this->authorize('manage_users');

    return DB::transaction(function () use ($request, $id) {
        $DamageProduct = DamageProduct::findOrFail($id);

        if (!$DamageProduct) {
            return response()->json([
                'message' => "DamageProduct not found."
            ], 404);
        }

        $repairedQty = $request->input('repaired_quantity', 0);

        if ($repairedQty <= 0) {
            throw new \Exception("من فضلك أدخل كمية صحيحة للإصلاح.");
        }

        if ($repairedQty > $DamageProduct->quantity) {
            throw new \Exception("الكمية التي تريد إصلاحها أكبر من الكمية التالفة.");
        }

        $shipmentProduct = ShipmentProduct::where('id', $DamageProduct->shipment_product_id)
            ->lockForUpdate()
            ->first();

        if (!$shipmentProduct) {
            throw new \Exception("ShipmentProduct not found.");
        }

        $shipmentProduct->increment('remainingQuantity', $repairedQty);

       
        $newDamageQty = $DamageProduct->quantity - $repairedQty;

        $DamageProduct->update([
            'quantity' => $newDamageQty,
            'status'   => $newDamageQty == 0 ? 'repaired' : 'damage', // لو كله اتصلح يبقى الحالة repaired
        ]);

        return response()->json([
            'data' => new DamageProductResource($DamageProduct),
            'message' => "تم إصلاح {$repairedQty} قطعة وإرجاعها للمخزون بنجاح."
        ]);
    });
}


public function return(SupplierReturnRequest $request, string $id)
{
    $this->authorize('manage_users');

    return DB::transaction(function () use ($request, $id) {
        $damageProduct = DamageProduct::findOrFail($id);

        $returnQty     = $request->input('returned_quantity', 0);
        $refundAmount  = $request->input('refund_amount', 0);
        $note          = $request->input('note', null);

        if ($returnQty <= 0) {
            return response()->json([
                'message' => "من فضلك أدخل كمية صحيحة للإرجاع."
            ], 422);
        }

        if ($returnQty > $damageProduct->quantity) {
            return response()->json([
                'message' => "الكمية التي تريد إرجاعها أكبر من الكمية التالفة."
            ], 422);
        }

        if ($refundAmount < 0) {
            return response()->json([
                'message' => "المبلغ المسترد لا يمكن أن يكون أقل من صفر."
            ], 422);
        }

        if ($refundAmount > ($returnQty * $damageProduct->shipmentProduct->unitPrice)) {
            return response()->json([
                'message' => "المبلغ المسترد لا يمكن أن يكون أكبر من إجمالي قيمة المنتجات المرجعة."
            ], 422);
        }

        $newDamageQty = $damageProduct->quantity - $returnQty;
        $damageProduct->update([
            'quantity' => $newDamageQty,
            'status'   => $newDamageQty == 0 ? 'return' : 'damage',
        ]);

        $expectedRefund = $returnQty * $damageProduct->shipmentProduct->unitPrice;
        $lossAmount     = $expectedRefund - $refundAmount;

        $supplierReturn = SupplierReturn::create([
            'damage_product_id' => $damageProduct->id,
            'returned_quantity' => $returnQty,
            'refund_amount'     => $refundAmount,
            'loss_amount'       => $lossAmount,
            'note'              => $note,
            'creationDate'      => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'data' => [
                'damage_product'   => new DamageProductResource($damageProduct),
                'supplier_return'  => new SupplierReturnResource($supplierReturn),
            ],
            'message' => "تم إرجاع {$returnQty} قطعة للمورد، المبلغ المسترد {$refundAmount}، والخسارة المسجلة {$lossAmount}."
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
