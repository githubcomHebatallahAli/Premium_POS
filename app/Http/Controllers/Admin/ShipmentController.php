<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Shipment;
use Illuminate\Http\Request;
use App\Traits\ManagesModelsTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShipmentRequest;
use App\Http\Resources\Admin\ShipmentResource;
use App\Http\Requests\Admin\UpdatePaidAmountRequest;
use App\Http\Resources\Admin\ShipmentProductResource;

class ShipmentController extends Controller
{
    use ManagesModelsTrait;
    // public function showAll(Request $request)
    // {

    //     $this->authorize('showAll',Shipment::class);

    //     $searchTerm = $request->input('search', '');

    //     $Shipment = Shipment::where('supplierName', 'like', '%' . $searchTerm . '%')
    //                  ->orderBy('created_at', 'desc')
    //                  ->paginate(10);

    //     $paidAmount = $Shipment->sum('paidAmount');
    //     $remainingAmount = $Shipment->sum('remainingAmount');

    //               return response()->json([
    //                   'data' =>  ShipmentResource::collection($Shipment),
    //                   'pagination' => [
    //                     'total' => $Shipment->total(),
    //                     'count' => $Shipment->count(),
    //                     'per_page' => $Shipment->perPage(),
    //                     'current_page' => $Shipment->currentPage(),
    //                     'total_pages' => $Shipment->lastPage(),
    //                     'next_page_url' => $Shipment->nextPageUrl(),
    //                     'prev_page_url' => $Shipment->previousPageUrl()
    //                 ],
    //                 'statistics' => [
    //                 'paid_amount' => number_format($paidAmount, 2, '.', ''),
    //                 'remaining_amount' => number_format($remainingAmount, 2, '.', ''),
    //                 ],

    //                   'message' => "Show All Shipment."
    //               ]);
    // }

    public function showAll(Request $request)
{
    $this->authorize('showAll', Shipment::class);

    $searchTerm = $request->input('search', '');
    $statusFilter = $request->input('status', '');

    // $query = Shipment::where('supplierName', 'like', '%' . $searchTerm . '%');
    $query = Shipment::with('supplier')
    ->when($searchTerm, function ($query) use ($searchTerm) {
        $query->whereHas('supplier', function ($q) use ($searchTerm) {
            $q->where('supplierName', 'like', '%' . $searchTerm . '%');
        });
    });

  if ($request->filled('status') && in_array($statusFilter, ['pending', 'paid', 'partialReturn', 'return'])) {
        $query->where('status', $statusFilter);
    }

    $shipments = $query->orderBy('created_at', 'desc')
                      ->paginate(10);

    $paidAmount = Shipment::sum('paidAmount');
    $remainingAmount = Shipment::where('status', 'pending')->sum('remainingAmount');

    return response()->json([
        'data' => ShipmentResource::collection($shipments),
        'pagination' => [
            'total' => $shipments->total(),
            'count' => $shipments->count(),
            'per_page' => $shipments->perPage(),
            'current_page' => $shipments->currentPage(),
            'total_pages' => $shipments->lastPage(),
            'next_page_url' => $shipments->nextPageUrl(),
            'prev_page_url' => $shipments->previousPageUrl()
        ],
        'statistics' => [
            'paid_amount' => number_format($paidAmount, 2, '.', ''),
            'remaining_amount' => number_format($remainingAmount, 2, '.', ''),
        ],
        'message' => "Show All Shipment."
    ]);
}


public function create(ShipmentRequest $request)
{
    $this->authorize('create',Shipment::class);

    $formattedTotalPrice = number_format($request->totalPrice, 2, '.', '');

    $Shipment = Shipment::create([
        // "supplierName" => $request->supplierName,
        "supplier_id" => $request->supplier_id,
        "importer" => $request->importer,
        'admin_id' => auth()->id(),
        'paidAmount' => $request->paidAmount ?? 0,
        'status' => 'pending',
        'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d h:i:s'),
    ]);

    if ($request->has('products')) {
        foreach ($request->products as $product) {
            $productModel = Product::find($product['id']);

            if (!$productModel) {
                return response()->json([
                    'message' => "Product with ID {$product['id']} not found.",
                ], 404);
            }

            $productModel->increment('quantity', $product['quantity']);

            $Shipment->products()->syncWithoutDetaching([
                $product['id'] => [
                    'quantity' => $product['quantity'],
                    'price' => $product['price']
                ]
            ]);
        }
    }

    $Shipment->updateShipmentProductsCount();

    $Shipment->totalPrice = $Shipment->calculateTotalPrice();

    $remainingAmount = $Shipment->totalPrice - $Shipment->paidAmount;
    $Shipment->remainingAmount = $remainingAmount;

    if ($Shipment->paidAmount >= $Shipment->totalPrice) {
        $Shipment->status = 'paid';
    } else {
        $Shipment->status = 'pending';
    }

    $Shipment->save();

    return response()->json([
        'data' => new ShipmentProductResource($Shipment),
        'message' => "Shipment Created Successfully.",
    ]);
}


public function updatePaidAmount(UpdatePaidAmountRequest $request, $id)
{

    $shipment = Shipment::findOrFail($id);
    $this->authorize('updatePaidAmount',$shipment);
    $paidAmount = $request->paidAmount;

    if ($paidAmount > $shipment->remainingAmount) {
        return response()->json([
            'message' => 'المبلغ المدفوع يتجاوز المبلغ المتبقي.',
        ], 400);
    }

    $shipment->paidAmount += $paidAmount;

    $remainingAmount = $shipment->totalPrice - $shipment->paidAmount;
    $shipment->remainingAmount = $remainingAmount;

    if ($remainingAmount <= 0) {
        $shipment->status = 'paid';
    } else {
        $shipment->status = 'pending';
    }

    $shipment->save();

    return response()->json([
        'message' => 'تم تحديث المبلغ المدفوع بنجاح.',
        'data' => new ShipmentProductResource($shipment),
    ]);
}


        public function edit(string $id)
        {
            $Shipment = Shipment::with('supplier')->find($id);

            if (!$Shipment) {
                return response()->json([
                    'message' => "Shipment not found."
                ], 404);
            }
            $this->authorize('edit',$Shipment);

            return response()->json([
                'data' => new ShipmentProductResource($Shipment),
                'message' => "Edit Shipment By ID Successfully."
            ]);
        }


    public function update(ShipmentRequest $request, string $id)
{

    $Shipment = Shipment::findOrFail($id);

    if (!$Shipment) {
        return response()->json([
            'message' => "Shipment not found."
        ], 404);
    }
    $this->authorize('update',$Shipment);

    $Shipment->update([
        "supplier_id" => $request->supplier_id,
        "importer" => $request->importer,
        // "place" => $request->place,
        'admin_id' => auth()->id(),
        'paidAmount' => $request->paidAmount ?? 0,
        'status' => 'pending',
        'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d h:i:s'),
    ]);

    $previousProducts = $Shipment->products()
        ->select('products.id', 'shipment_products.quantity')
        ->pluck('shipment_products.quantity', 'products.id')
        ->toArray();

    if ($request->has('products')) {
        $productsData = [];
        $errors = [];

        foreach ($request->products as $product) {
            $productModel = Product::find($product['id']);
            $previousQuantity = $previousProducts[$product['id']] ?? 0;
            $newQuantity = $product['quantity'];

            if ($newQuantity > $previousQuantity) {
                $difference = $newQuantity - $previousQuantity;
                $productModel->increment('quantity', $difference);
            } elseif ($newQuantity < $previousQuantity) {
                $difference = $previousQuantity - $newQuantity;
                if ($productModel->quantity < $difference) {
                    $errors[] = "Not enough quantity to reduce for product '{$productModel->name}'. Available: {$productModel->quantity}.";
                    continue;
                }
                $productModel->decrement('quantity', $difference);
            }

            $productsData[$product['id']] = [
                'quantity' => $newQuantity,
                'price' => $product['price'],
            ];
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Some errors occurred while updating the shipment.',
                'errors' => $errors,
            ], 400);
        }

        $Shipment->products()->sync($productsData);
    }

    $Shipment->updateShipmentProductsCount();

    $Shipment->totalPrice = $Shipment->calculateTotalPrice();

    $remainingAmount = $Shipment->totalPrice - $Shipment->paidAmount;
    $Shipment->remainingAmount = $remainingAmount;

    if ($Shipment->paidAmount >= $Shipment->totalPrice) {
        $Shipment->status = 'paid';
    } else {
        $Shipment->status = 'pending';
    }
    $Shipment->save();

    return response()->json([
        'data' => new ShipmentProductResource($Shipment->load('products')),
        'message' => "Update Shipment By Id Successfully.",
    ]);
}


    public function destroy(string $id){

    return $this->destroyModel(Shipment::class, ShipmentProductResource::class, $id);
    }

    public function showDeleted(){
        $this->authorize('manage_users');
    $Shipments=Shipment::onlyTrashed()->get();
    return response()->json([
        'data' =>ShipmentProductResource::collection($Shipments),
        'message' => "Show Deleted Shipments Successfully."
    ]);
    }

    public function restore(string $id)
    {
       $this->authorize('manage_users');
    $Shipment = Shipment::withTrashed()->where('id', $id)->first();
    if (!$Shipment) {
        return response()->json([
            'message' => "Shipment not found."
        ], 404);
    }
    $Shipment->restore();
    return response()->json([
        'data' =>new ShipmentProductResource($Shipment),
        'message' => "Restore Shipment By Id Successfully."
    ]);
    }

    public function forceDelete(string $id){

        return $this->forceDeleteModel(Shipment::class, $id);
    }
}
