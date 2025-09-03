<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Agentinvoice;
use App\Traits\ManagesModelsTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AgentInvoiceRequest;
use App\Http\Resources\Admin\AgentInvoiceResource;


class AgentInvoiceController extends Controller
{

    use ManagesModelsTrait;
    public function showAll()
    {
        $this->authorize('manage_users');

        $AgentInvoices = Agentinvoice::orderBy('created_at', 'desc')->paginate(10);
        return response()->json([
            'data' => $AgentInvoices->map(function ($AgentInvoice) {
                return [
                    'id' => $AgentInvoice->id,
                    'distributorName' => $AgentInvoice->distributorName,
                    'status' => $AgentInvoice->status,
                    'creationDate' => $AgentInvoice->creationDate,
                    'totalInvoicePrice' => $AgentInvoice-> totalInvoicePrice,
                ];
            }),
              'pagination' => [
                        'total' => $AgentInvoices->total(),
                        'count' => $AgentInvoices->count(),
                        'per_page' => $AgentInvoices->perPage(),
                        'current_page' => $AgentInvoices->currentPage(),
                        'total_pages' => $AgentInvoices->lastPage(),
                        'next_page_url' => $AgentInvoices->nextPageUrl(),
                        'prev_page_url' => $AgentInvoices->previousPageUrl(),
                    ],
            'message' => "Show All AgentInvoices Successfully."
        ]);
    }


public function create(AgentInvoiceRequest $request)
{
    $this->authorize('manage_users');

    $AgentInvoice = Agentinvoice::create([
        "distributorName" => $request->distributorName,
        "responsibleName" => $request->responsibleName,
        "status" => 'distribution',
        'creationDate' => now()->timezone('Africa/Cairo')
            ->format('Y-m-d h:i:s'),
    ]);

    // $totalProfit = 0;
    $totalSellingPrice = 0;
    // $extraAmount = $request->extraAmount ?? 0;

    if ($request->has('products')) {
        foreach ($request->products as $product) {
            $productModel = Product::find($product['id']);

            if ($productModel->quantity <= 0) {
                return response()->json([
                    'message' => "Product '{$productModel->name}' is out of stock and cannot be added to the AgentInvoice.",
                ], 400);
            }

            if ($product['quantity'] > $productModel->quantity) {
                return response()->json([
                    'message' => "Not enough quantity for product '{$productModel->name}'. Available: {$productModel->quantity}.",
                ], 400);
            }

            $productModel->decrement('quantity', $product['quantity']);

            if ($productModel->quantity === 0) {
                $outOfStockProducts[] = $productModel->name;
            }

            $totalSellingPriceForProduct = $productModel->sellingPrice * $product['quantity'];
            $totalSellingPrice += $totalSellingPriceForProduct;

            // $profitForProduct = ($productModel->sellingPrice - $productModel->purchesPrice) * $product['quantity'];
            // $totalProfit += $profitForProduct;

            $AgentInvoice->products()->attach($product['id'], [
                'quantity' => $product['quantity'],
                'total' => $totalSellingPriceForProduct,
                // 'profit' => $profitForProduct,
            ]);
        }
    }

    // $discount = $AgentInvoice->discount ?? 0;
    // $totalSellingPrice += $extraAmount;
    // $finalPrice = $totalSellingPrice - $discount;
    // $netProfit = $totalProfit - $discount;

    $formattedTotalSellingPrice = number_format($totalSellingPrice, 2, '.', '');
    // $formattedFinalPrice = number_format($finalPrice, 2, '.', '');
    // $formattedNetProfit = number_format($netProfit, 2, '.', '');
    // $formattedDiscount = number_format($discount, 2, '.', '');
    // $formattedExtraAmount = number_format($extraAmount, 2, '.', '');

    $AgentInvoice->update([
        'totalInvoicePrice' => $formattedTotalSellingPrice,
        // 'AgentInvoiceAfterDiscount' => $formattedFinalPrice,
        // 'profit' => $formattedNetProfit,
    ]);

    $AgentInvoice->updateInvoiceProductCount();

    $warningMessage = null;
    if (!empty($outOfStockProducts)) {
        $warningMessage = "The following products are now out of stock: " . implode(', ', $outOfStockProducts);
    }

    return response()->json([
        'message' => 'AgentInvoice created successfully',
        'AgentInvoice' => new AgentInvoiceResource($AgentInvoice->load('products')),
        // 'extraAmount' => $formattedExtraAmount,
        'totalInvoicePrice' => $formattedTotalSellingPrice,
        // 'discount' => $formattedDiscount,
        // 'InvoiceAfterDiscount' => $formattedFinalPrice,
        'warning' => $warningMessage,
    ]);
}

    public function edit(string $id)
    {
        $this->authorize('manage_users');
        $AgentInvoice = AgentInvoice::with('products')->find($id);

        if (!$AgentInvoice) {
            return response()->json([
                'message' => "AgentInvoice not found."
            ], 404);
        }
        $totalAgentInvoicePrice = $AgentInvoice->load('products')->calculateTotalPrice();

        // $finalPrice = $totalAgentInvoicePrice - ($AgentInvoice->discount ?? 0);


        $AgentInvoice->update([
            'totalInvoicePrice' => $totalAgentInvoicePrice,
            // 'AgentInvoiceAfterDiscount' => $finalPrice,
        ]);


        return response()->json([
            'message' => 'AgentInvoice update successfully',
            'AgentInvoice' => new AgentInvoiceResource($AgentInvoice->load('products')),
            'totalInvoicePrice' => $totalAgentInvoicePrice,
            // 'discount' => $AgentInvoice->discount,
            // 'AgentInvoiceAfterDiscount' => $finalPrice,
        ]);
    }


    public function update(AgentInvoiceRequest $request, string $id)
{

    $this->authorize('manage_users');

    $AgentInvoice = Agentinvoice::findOrFail($id);

    if (!$AgentInvoice) {
        return response()->json([
            'message' => "AgentInvoice not found."
        ], 404);
    }
    $previousProducts = $AgentInvoice->products()
        ->select('products.id', 'AgentInvoice_products.quantity')
        ->pluck('AgentInvoice_products.quantity', 'products.id')
        ->toArray();

    $AgentInvoice->update([
        "distributorName" => $request->distributorName,
        "responsibleName" => $request->responsibleName,
        "status" => $request-> status,
        // "discount" => $request->discount,
        // "extraAmount" => $request->extraAmount ?? 0,
        'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d h:i:s'),
    ]);

    // $totalProfit = 0;
    $totalSellingPrice = 0;
    // $extraAmount = $request->extraAmount ?? 0;

    $outOfStockProducts = [];


    if ($request->has('products')) {
        $productsData = [];
        $errors = [];

        foreach ($request->products as $product) {
            $productModel = Product::find($product['id']);
            $previousQuantity = $previousProducts[$product['id']] ?? 0;
            $newQuantity = $product['quantity'];

            if ($newQuantity > $previousQuantity) {
                $difference = $newQuantity - $previousQuantity;

                if ($difference > $productModel->quantity) {
                    $errors[] = "Not enough quantity for product '{$productModel->name}'. Available: {$productModel->quantity}.";
                    continue;
                }


                $productModel->decrement('quantity', $difference);
            } elseif ($newQuantity < $previousQuantity) {
                $difference = $previousQuantity - $newQuantity;


                $productModel->increment('quantity', $difference);
            }


            if ($productModel->quantity <= 0) {
                $outOfStockProducts[] = $productModel->name;
            }


            $totalSellingPriceForProduct = $productModel->sellingPrice * $newQuantity;
            $totalSellingPrice += $totalSellingPriceForProduct;

            // $profitForProduct = ($productModel->sellingPrice - $productModel->purchesPrice) * $newQuantity;
            // $totalProfit += $profitForProduct;

            $productsData[$product['id']] = [
                'quantity' => $newQuantity,
                'total' => $totalSellingPriceForProduct,
                // 'profit' => $profitForProduct,
            ];
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Some errors occurred while processing the AgentInvoice.',
                'errors' => $errors,
            ], 400);
        }

        $warningMessage = null;
        if (!empty($outOfStockProducts)) {
            $warningMessage = "The following products are now out of stock: " . implode(', ', $outOfStockProducts);
        }

        $AgentInvoice->products()->sync($productsData);
    }

    // $discount = $AgentInvoice->discount ?? 0;
    // $totalSellingPrice += $extraAmount;
    // $finalPrice = $totalSellingPrice - $discount;
    // $netProfit = $totalProfit - $discount;

    $formattedTotalSellingPrice = number_format($totalSellingPrice, 2, '.', '');
    // $formattedFinalPrice = number_format($finalPrice, 2, '.', '');
    // $formattedNetProfit = number_format($netProfit, 2, '.', '');
    // $formattedDiscount = number_format($discount, 2, '.', '');
    // $formattedExtraAmount = number_format($extraAmount, 2, '.', '');

    $AgentInvoice->update([
        'totalAgentInvoicePrice' => $formattedTotalSellingPrice,
        // 'AgentInvoiceAfterDiscount' => $formattedFinalPrice,
        // 'profit' => $formattedNetProfit,
    ]);

    $AgentInvoice->updateInvoiceProductCount();
    // dd($AgentInvoice);
    return response()->json([
        'message' => 'AgentInvoice updated successfully.',
        'AgentInvoice' => new AgentInvoiceResource($AgentInvoice->load('products')),
        // 'extraAmount' => $formattedExtraAmount,
        'totalAgentInvoicePrice' => $formattedTotalSellingPrice,
        // 'discount' => $formattedDiscount,
        // 'AgentInvoiceAfterDiscount' => $formattedFinalPrice,
        'warning' => $warningMessage,
    ]);
}




  public function destroy(string $id)
  {
      return $this->destroyModel(AgentInvoice::class, AgentInvoiceResource::class, $id);
  }

  public function showDeleted()
  {
    $this->authorize('manage_users');
$AgentInvoices=AgentInvoice::onlyTrashed()->get();
return response()->json([
    'data' =>AgentInvoiceResource::collection($AgentInvoices),
    'message' => "Show Deleted AgentInvoices Successfully."
]);

}

public function restore(string $id)
{
   $this->authorize('manage_users');
$AgentInvoice = AgentInvoice::withTrashed()->where('id', $id)->first();
if (!$AgentInvoice) {
    return response()->json([
        'message' => "AgentInvoice not found."
    ], 404);
}
$AgentInvoice->restore();
return response()->json([
    'data' =>new AgentInvoiceResource($AgentInvoice),
    'message' => "Restore AgentInvoice By Id Successfully."
]);
}

  public function forceDelete(string $id)
  {
      return $this->forceDeleteModel(Agentinvoice::class, $id);
  }

    public function distribution(string $id)
{
    $this->authorize('manage_users');
    $AgentInvoice =Agentinvoice::findOrFail($id);

    if (!$AgentInvoice) {
     return response()->json([
         'message' => "AgentInvoice not found."
     ], 404);
 }

    $AgentInvoice->update(['status' => 'distribution']);

    return response()->json([
        'data' => new AgentInvoiceResource($AgentInvoice),
        'message' => 'AgentInvoice has been distribution.'
    ]);
}

public function delivery(string $id)
{
    $this->authorize('manage_users');
    $AgentInvoice = Agentinvoice::findOrFail($id);

    if (!$AgentInvoice) {
        return response()->json([
            'message' => "AgentInvoice not found."
        ], 404);
    }

    // استرجاع المنتجات المرتبطة بالفاتورة
    $products = $AgentInvoice->products;

    // زيادة كمية المنتجات في جدول المنتجات
    foreach ($products as $product) {
        $productModel = Product::find($product->id);
        $productModel->increment('quantity', $product->pivot->quantity);
    }

    // تحديث حالة الفاتورة إلى "تم التسليم"
    $AgentInvoice->update(['status' => 'delivery']);

    return response()->json([
        'data' => new AgentInvoiceResource($AgentInvoice),
        'message' => 'AgentInvoice has been delivered and product quantities have been restored.'
    ]);
}

}
