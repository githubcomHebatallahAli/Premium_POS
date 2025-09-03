<?php

namespace App\Http\Controllers\Admin;

use App\Models\Dept;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Traits\ManagesModelsTrait;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeptRequest;
use App\Http\Resources\Admin\DeptResource;
use App\Http\Requests\Admin\UpdatePaidAmountRequest;


class DeptController extends Controller
{
    use ManagesModelsTrait;

public function showAll(Request $request)
{
    $this->authorize('showAll', Dept::class);

    $searchTerm = $request->input('search', '');
    $statusFilter = $request->input('status', '');

    $query = Dept::where('customerName', 'like', '%' . $searchTerm . '%');

    if ($statusFilter === 'paid') {
        $query->where('status', 'paid');
    } elseif ($statusFilter === 'unpaid') {
        $query->where('status', 'pending');
    }

    $depts = $query->orderBy('created_at', 'desc')
                   ->paginate(10);


    $totalPaidAmount = Dept::sum('paidAmount');
    $totalRemainingAmount = Dept::where('status', 'pending')->sum('remainingAmount');

    return response()->json([
        'data' => $depts->map(function ($dept) {
            return [
                'id' => $dept->id,
                'customerName' => $dept->customerName,
                'status' => $dept->status,
                'paidAmount' => $dept->paidAmount,
                'remainingAmount' => $dept->remainingAmount,
                'depetAfterDiscount' => $dept->depetAfterDiscount,
                'creationDate' => $dept->creationDate,
            ];
        }),
            'pagination' => [
            'total' => $depts->total(),
            'count' => $depts->count(),
            'per_page' => $depts->perPage(),
            'current_page' => $depts->currentPage(),
            'total_pages' => $depts->lastPage(),
            'next_page_url' => $depts->nextPageUrl(),
            'prev_page_url' => $depts->previousPageUrl(),
        ],
        'statistics' => [
            'paid_amount' => $totalPaidAmount,
            'remaining_amount' => $totalRemainingAmount,
        ],
        'message' => "تم عرض الفواتير بنجاح."
    ]);
}

    public function create(DeptRequest $request)
    {
        $this->authorize('create',Dept::class);

        $Dept = Dept::create([
            "customerName" => $request->customerName,
            "sellerName" => $request->sellerName,
            "discount" => $request->discount ?? 0,
            "extraAmount" => $request->extraAmount ?? 0,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            'paidAmount' => $request->paidAmount ?? 0,
        ]);

        $totalProfit = 0;
        $totalDeptPrice = 0;
        $extraAmount = $request->extraAmount ?? 0;
        $outOfStockProducts = [];

        if ($request->has('products')) {
            foreach ($request->products as $product) {
                $productModel = Product::find($product['id']);

                if ($productModel->quantity <= 0) {
                    return response()->json([
                        'message' => "Product '{$productModel->name}' is out of stock and cannot be added.",
                    ], 400);
                }

                if ($product['quantity'] > $productModel->quantity) {
                    return response()->json([
                        'message' => "Not enough quantity for '{$productModel->name}'. Available: {$productModel->quantity}.",
                    ], 400);
                }

                $productModel->decrement('quantity', $product['quantity']);

                if ($productModel->quantity === 0) {
                    $outOfStockProducts[] = $productModel->name;
                }

                $totalDeptPriceForProduct = $productModel->sellingPrice * $product['quantity'];
                $totalDeptPrice += $totalDeptPriceForProduct;

                $profitForProduct = ($productModel->sellingPrice - $productModel->purchesPrice) * $product['quantity'];
                $totalProfit += $profitForProduct;

                $Dept->products()->attach($product['id'], [
                    'quantity' => $product['quantity'],
                    'total' => $totalDeptPriceForProduct,
                    'profit' => $profitForProduct,
                ]);
            }
        }

        $discount = $Dept->discount ?? 0;
        $totalDeptPrice += $extraAmount;
        $finalDeptPrice = $totalDeptPrice - $discount;
        $netProfit = $totalProfit - $discount;
        $remainingAmount = $finalDeptPrice - $Dept->paidAmount;

        $formattedTotalDeptPrice = number_format($totalDeptPrice, 2, '.', '');
        $formattedFinalDeptPrice = number_format($finalDeptPrice, 2, '.', '');
        $formattedNetProfit = number_format($netProfit, 2, '.', '');
        $formattedDiscount = number_format($discount, 2, '.', '');
        $formattedExtraAmount = number_format($extraAmount, 2, '.', '');
        $formattedRemainingAmount = number_format($remainingAmount, 2, '.', '');

        $Dept->totalDepetPrice = $formattedTotalDeptPrice;
        $Dept->depetAfterDiscount = $formattedFinalDeptPrice;
        $Dept->profit = $formattedNetProfit;
        $Dept->remainingAmount = $formattedRemainingAmount;
        $Dept->status = $remainingAmount > 0 ? 'pending' : 'paid';
        $Dept->save();

        $Dept->updateDeptProductCount();

        $warningMessage = null;
        if (!empty($outOfStockProducts)) {
            $warningMessage = "The following products are now out of stock: " . implode(', ', $outOfStockProducts);
        }

        return response()->json([
            'message' => 'Dept record created successfully',
            'dept' => new DeptResource($Dept->load('products')),
            'extraAmount' => $formattedExtraAmount,
            'totalDepetPrice' => $formattedTotalDeptPrice,
            'discount' => $formattedDiscount,
            'depetAfterDiscount' => $formattedFinalDeptPrice,
            'paidAmount' => number_format($Dept->paidAmount, 2, '.', ''),
            'remainingAmount' => $formattedRemainingAmount,
            'warning' => $warningMessage,
        ]);
    }


public function updatePaidAmount(UpdatePaidAmountRequest $request, $id)
{
    // $this->authorize('manage_users');
    $Dept = Dept::findOrFail($id);
    $this->authorize('updatePaidAmount',$Dept);

    $paidAmount = $request->paidAmount;
    $Dept->paidAmount += $paidAmount;

    $remainingAmount = $Dept->depetAfterDiscount - $Dept->paidAmount;

    $Dept->remainingAmount = number_format($remainingAmount, 2, '.', '');
    $Dept->status = $remainingAmount > 0 ? 'pending' : 'paid';
    $Dept->save();

    return response()->json([
        'message' => 'Paid amount updated successfully',
        'dept' => new DeptResource($Dept->load('products')),
        'extraAmount' => number_format($Dept->extraAmount, 2, '.', ''),
        'totalDepetPrice' => number_format($Dept->totalDepetPrice, 2, '.', ''),
        'discount' => number_format($Dept->discount, 2, '.', ''),
        'depetAfterDiscount' => number_format($Dept->depetAfterDiscount, 2, '.', ''),
        'paidAmount' => number_format($Dept->paidAmount, 2, '.', ''),
        'remainingAmount' => number_format($remainingAmount, 2, '.', ''),
    ]);
}


public function edit(string $id)
{
    $dept = Dept::with('products')->find($id);

    if (!$dept) {
        return response()->json([
            'message' => "Dept record not found."
        ], 404);
    }
    $this->authorize('edit',$dept);

    $totalDeptPrice = 0;
    $extraAmount = $dept->extraAmount ?? 0;
    $paidAmount = $dept->paidAmount ?? 0;
    $discount = $dept->discount ?? 0;

    if ($dept->products->isNotEmpty()) {
        foreach ($dept->products as $product) {
            $totalDeptPrice += $product->pivot->total;
        }
    }

    $totalDeptPrice += $extraAmount;

    $remainingAmount = max(0, $totalDeptPrice - $paidAmount);
    $deptAfterDiscount = $totalDeptPrice - $discount;

    $formattedPaidAmount = number_format($paidAmount, 2, '.', '');
    $formattedTotalDeptPrice = number_format($totalDeptPrice, 2, '.', '');
    $formattedRemainingAmount = number_format($remainingAmount, 2, '.', '');
    $formattedDeptAfterDiscount = number_format($deptAfterDiscount, 2, '.', '');
    $formattedExtraAmount = number_format($extraAmount, 2, '.', '');
    $formattedDiscount = number_format($discount, 2, '.', '');

    $dept->update([
        'totalDepetPrice' => $formattedTotalDeptPrice,
        'remainingAmount' => $formattedRemainingAmount,
        'depetAfterDiscount' => $formattedDeptAfterDiscount,
    ]);

    return response()->json([
        'message' => 'Dept details fetched successfully',
        'dept' => new DeptResource($dept->load('products')),
        'extraAmount' => $formattedExtraAmount,
        'totalDeptPrice' => $formattedTotalDeptPrice,
        'discount' => $formattedDiscount,
        'deptAfterDiscount' => $formattedDeptAfterDiscount,
        'paidAmount' => $formattedPaidAmount,
        'remainingAmount' => $formattedRemainingAmount,
    ]);
}

public function update(DeptRequest $request, string $id)
{
    // $this->authorize('manage_users');

    $Dept = Dept::findOrFail($id);

    if (!$Dept) {
        return response()->json([
            'message' => "Dept not found."
        ]);
    }
    $this->authorize('update',$Dept);
    $previousProducts = $Dept->products()
        ->select('products.id', 'dept_products.quantity')
        ->pluck('dept_products.quantity', 'products.id')
        ->toArray();

    $Dept->update([
        "customerName" => $request->customerName,
        "sellerName" => $request->sellerName,
        "discount" => $request->discount ?? 0,
        "extraAmount" => $request->extraAmount ?? 0,
        'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
        'paidAmount' => $request->paidAmount ?? 0,
    ]);

    $totalProfit = 0;
    $totalDeptPrice = 0;
    $extraAmount = $request->extraAmount ?? 0;
    $outOfStockProducts = [];
    $productsData = [];
    $errors = [];

    if ($request->has('products')) {
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

            if ($productModel->quantity === 0) {
                $outOfStockProducts[] = $productModel->name;
            }

            $totalDeptPriceForProduct = $productModel->sellingPrice * $newQuantity;
            $totalDeptPrice += $totalDeptPriceForProduct;

            $profitForProduct = ($productModel->sellingPrice - $productModel->purchesPrice) * $newQuantity;
            $totalProfit += $profitForProduct;

            $productsData[$product['id']] = [
                'quantity' => $newQuantity,
                'total' => $totalDeptPriceForProduct,
                'profit' => $profitForProduct,
            ];
        }

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Some errors occurred while processing the dept update.',
                'errors' => $errors,
            ], 400);
        }

        $Dept->products()->sync($productsData);
    }

    $discount = $Dept->discount ?? 0;
    $totalDeptPrice += $extraAmount;
    $finalDeptPrice = $totalDeptPrice - $discount;
    $netProfit = $totalProfit - $discount;
    $remainingAmount = $finalDeptPrice - $Dept->paidAmount;

    $formattedTotalDeptPrice = number_format($totalDeptPrice, 2, '.', '');
    $formattedFinalDeptPrice = number_format($finalDeptPrice, 2, '.', '');
    $formattedNetProfit = number_format($netProfit, 2, '.', '');
    $formattedDiscount = number_format($discount, 2, '.', '');
    $formattedExtraAmount = number_format($extraAmount, 2, '.', '');
    $formattedRemainingAmount = number_format($remainingAmount, 2, '.', '');

    $Dept->update([
        'totalDepetPrice' => $formattedTotalDeptPrice,
        'depetAfterDiscount' => $formattedFinalDeptPrice,
        'profit' => $formattedNetProfit,
        'remainingAmount' => $formattedRemainingAmount,
        'status' => $remainingAmount > 0 ? 'pending' : 'paid',
    ]);

    $Dept->updateDeptProductCount();

    $warningMessage = !empty($outOfStockProducts)
        ? "The following products are now out of stock: " . implode(', ', $outOfStockProducts)
        : null;

    return response()->json([
        'message' => 'Dept record updated successfully.',
        'dept' => new DeptResource($Dept->load('products')),
        'extraAmount' => $formattedExtraAmount,
        'totalDepetPrice' => $formattedTotalDeptPrice,
        'discount' => $formattedDiscount,
        'depetAfterDiscount' => $formattedFinalDeptPrice,
        'paidAmount' => number_format($Dept->paidAmount, 2, '.', ''),
        'remainingAmount' => $formattedRemainingAmount,
        'warning' => $warningMessage,
    ]);
}

public function destroy(string $id)
{
    return $this->destroyModel(Dept::class, DeptResource::class, $id);
}

public function showDeleted()
{
  $this->authorize('manage_users');
$Depts=Dept::onlyTrashed()->get();
return response()->json([
  'data' =>DeptResource::collection($Depts),
  'message' => "Show Deleted Depts Successfully."
]);

}

public function restore(string $id)
{
 $this->authorize('manage_users');
$Dept = Dept::withTrashed()->where('id', $id)->first();
if (!$Dept) {
  return response()->json([
      'message' => "Dept not found."
  ], 404);
}
$Dept->restore();
return response()->json([
  'data' =>new DeptResource($Dept),
  'message' => "Restore Dept By Id Successfully."
]);
}

public function forceDelete(string $id)
{
    return $this->forceDeleteModel(Dept::class, $id);
}


}
