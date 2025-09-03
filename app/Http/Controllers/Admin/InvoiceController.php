<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvoiceRequest;
use App\Http\Requests\Admin\UpdatePaidAmountRequest;
use App\Http\Resources\Admin\InvoiceResource;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ShipmentProduct;
use App\Services\InvoiceService;
use App\Traits\ManagesModelsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class InvoiceController extends Controller
{
    use ManagesModelsTrait;
    public function __construct(private InvoiceService $invoiceService) {}

    public function create(InvoiceRequest $request)
    {
        $invoice = $this->invoiceService->create($request->validated());

        return response()->json([
            'message' => 'Invoice created successfully',
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function update(InvoiceRequest $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $updated = $this->invoiceService->update($invoice, $request->validated());

        return response()->json([
            'message' => 'Invoice updated successfully',
            'data' => new InvoiceResource($updated),
        ]);
    }

    public function edit($id)
    {
        $invoice = Invoice::with('products')->findOrFail($id);

        $total = $invoice->products->sum('pivot.total');
        $profit = $invoice->products->sum('pivot.profit');

        $calculated = $this->invoiceService->calculateTotals($invoice, $total, $profit);

        $invoice->update($calculated);

        return response()->json([
            'message' => 'Invoice fetched successfully',
            'data' => new InvoiceResource($invoice->fresh('products')),
        ]);
    }

    public function fullReturn($id)
    {
        $invoice = Invoice::findOrFail($id);

        $returned = $this->invoiceService->fullReturn($invoice);

        return response()->json([
            'message' => 'Invoice fully returned',
            'data' => new InvoiceResource($returned),
        ]);
    }

    public function partialReturn(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $returned = $this->invoiceService->partialReturn($invoice, $request->input('products', []));

        return response()->json([
            'message' => 'Invoice partially returned',
            'data' => new InvoiceResource($returned),
        ]);
    }
    
public function showAll(Request $request)
{
    // $this->authorize('showAll', Invoice::class);

    $searchTerm = $request->input('search', '');
    $status = $request->input('status', null);

    $Invoices = Invoice::query()
        ->when($searchTerm, function ($query) use ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('customerName', 'like', '%' . $searchTerm . '%')
                  ->orWhere('customerPhone', 'like', '%' . $searchTerm . '%');
            });
        })
        ->when($status, function ($query) use ($status) {
            $query->where('status', $status);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(10);

    // 📊 إحصائيات عامة
    $paidAmount      = Invoice::sum('paidAmount'); 
    $remainingAmount = Invoice::where('status', 'indebted')->sum('remainingAmount'); 
    $totalSales      = Invoice::where('status', 'completed')->sum('invoiceAfterDiscount'); 
    $totalProfit     = Invoice::where('status', 'completed')->sum('profit'); 

    return response()->json([
        'data' => $Invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'customerName' => $invoice->customerName,
                'customerPhone' => $invoice->customerPhone,
                'invoiceAfterDiscount' => $invoice->invoiceAfterDiscount,
                'creationDate' => $invoice->creationDate,
                'remainingAmount' => $invoice->remainingAmount,
                'status' => $invoice->status,
            ];
        }),
        'pagination' => [
            'total' => $Invoices->total(),
            'count' => $Invoices->count(),
            'per_page' => $Invoices->perPage(),
            'current_page' => $Invoices->currentPage(),
            'total_pages' => $Invoices->lastPage(),
            'next_page_url' => $Invoices->nextPageUrl(),
            'prev_page_url' => $Invoices->previousPageUrl(),
        ],
        'statistics' => [
            'paid_amount'       => number_format($paidAmount, 2, '.', ''),
            'remaining_amount'  => number_format($remainingAmount, 2, '.', ''),
            'total_sales'       => number_format($totalSales, 2, '.', ''),
            'total_profit'      => number_format($totalProfit, 2, '.', ''),
        ],
        'message' => "Show All Invoices Successfully."
    ]);
}


// public function showAll(Request $request)
// {
//     // $this->authorize('showAll', Invoice::class);

//     $searchTerm = $request->input('search', '');
//     $status = $request->input('status', null);
//     $fromDate = $request->input('from_date'); // YYYY-MM-DD
//     $toDate = $request->input('to_date');     // YYYY-MM-DD

//     $query = Invoice::query()
//         ->when($searchTerm, function ($q) use ($searchTerm) {
//             $q->where(function ($sub) use ($searchTerm) {
//                 $sub->where('customerName', 'like', '%' . $searchTerm . '%')
//                     ->orWhere('customerPhone', 'like', '%' . $searchTerm . '%');
//             });
//         })
//         ->when($status, function ($q) use ($status) {
//             $q->where('status', $status);
//         })
//         ->when($fromDate, function ($q) use ($fromDate) {
//             $q->whereDate('creationDate', '>=', $fromDate);
//         })
//         ->when($toDate, function ($q) use ($toDate) {
//             $q->whereDate('creationDate', '<=', $toDate);
//         })
//         ->orderBy('created_at', 'desc');

//     $Invoices = $query->paginate(10);

//     // استعلام الإحصائيات على نفس الفلاتر
//     $filteredQuery = (clone $query);

//     $paidAmount = $filteredQuery->sum('paidAmount');
//     $remainingAmount = $filteredQuery->where('status', 'indebted')->sum('remainingAmount');
//     $totalSales = $filteredQuery->where('status', 'completed')->sum('invoiceAfterDiscount');
//     $totalProfit = $filteredQuery->where('status', 'completed')->sum('profit');

//     return response()->json([
//         'data' => $Invoices->map(function ($invoice) {
//             return [
//                 'id' => $invoice->id,
//                 'customerName' => $invoice->customerName,
//                 'customerPhone' => $invoice->customerPhone,
//                 'invoiceAfterDiscount' => $invoice->invoiceAfterDiscount,
//                 'creationDate' => $invoice->creationDate,
//                 'remainingAmount' => $invoice->remainingAmount,
//                 'status' => $invoice->status,
//             ];
//         }),
//         'pagination' => [
//             'total' => $Invoices->total(),
//             'count' => $Invoices->count(),
//             'per_page' => $Invoices->perPage(),
//             'current_page' => $Invoices->currentPage(),
//             'total_pages' => $Invoices->lastPage(),
//             'next_page_url' => $Invoices->nextPageUrl(),
//             'prev_page_url' => $Invoices->previousPageUrl(),
//         ],
//         'statistics' => [
//             'paid_amount'      => number_format($paidAmount, 2, '.', ''),
//             'remaining_amount' => number_format($remainingAmount, 2, '.', ''),
//             'total_sales'      => number_format($totalSales, 2, '.', ''),
//             'total_profit'     => number_format($totalProfit, 2, '.', ''),
//         ],
//         'message' => "Show All Invoices Successfully."
//     ]);
// }


// public function showAll(Request $request)
// {
//     // قراءة الفلاتر من الطلب
//     $searchTerm = $request->input('search', '');
//     $status     = $request->input('status', null);
//     $fromDate   = $request->input('from_date');
//     $toDate     = $request->input('to_date');

//     // بناء الاستعلام الأساسي مع كل الفلاتر
//     $query = Invoice::query()
//         ->when($searchTerm, fn($q) => $q->where(fn($sub) =>
//             $sub->where('customerName', 'like', "%{$searchTerm}%")
//                 ->orWhere('customerPhone', 'like', "%{$searchTerm}%")
//         ))
//         ->when($status, fn($q) => $q->where('status', $status))
//         ->when($fromDate, fn($q) => $q->whereDate('creationDate', '>=', $fromDate))
//         ->when($toDate, fn($q) => $q->whereDate('creationDate', '<=', $toDate));

//     // استعلام Pagination
//     $Invoices = $query->orderBy('created_at', 'desc')->paginate(10);

//     // الإحصائيات على نفس الاستعلام الأساسي
//     $paidAmount      = (clone $query)->sum('paidAmount');
//     $remainingAmount = (clone $query)->where('status', 'indebted')->sum('remainingAmount');
//     $totalSales      = (clone $query)->where('status', 'completed')->sum('invoiceAfterDiscount');
//     $totalProfit     = (clone $query)->where('status', 'completed')->sum('profit');

//     return response()->json([
//         'data' => $Invoices->map(fn($invoice) => [
//             'id'                  => $invoice->id,
//             'customerName'        => $invoice->customerName,
//             'customerPhone'       => $invoice->customerPhone,
//             'invoiceAfterDiscount'=> $invoice->invoiceAfterDiscount,
//             'creationDate'        => $invoice->creationDate,
//             'remainingAmount'     => $invoice->remainingAmount,
//             'status'              => $invoice->status,
//         ]),
//         'pagination' => [
//             'total'         => $Invoices->total(),
//             'count'         => $Invoices->count(),
//             'per_page'      => $Invoices->perPage(),
//             'current_page'  => $Invoices->currentPage(),
//             'total_pages'   => $Invoices->lastPage(),
//             'next_page_url' => $Invoices->nextPageUrl(),
//             'prev_page_url' => $Invoices->previousPageUrl(),
//         ],
//         'statistics' => [
//             'paid_amount'      => number_format($paidAmount, 2, '.', ''),
//             'remaining_amount' => number_format($remainingAmount, 2, '.', ''),
//             'total_sales'      => number_format($totalSales, 2, '.', ''),
//             'total_profit'     => number_format($totalProfit, 2, '.', ''),
//         ],
//         'message' => "Show All Invoices Successfully."
//     ]);
// }


// ==========


// public function create(InvoiceRequest $request)
// {
//     DB::beginTransaction();
//     try {
//         $data = $request->validated();

//         $invoice = Invoice::create([
//             "customerName" => $data['customerName'],
//             "customerPhone" => $data['customerPhone'],
//             "admin_id" => auth()->id(),
//             "discount" => $data['discount'] ?? 0,
//             "extraAmount" => $data['extraAmount'] ?? 0,
//             "paidAmount" => $data['paidAmount'] ?? 0,
//             "creationDate" => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
//             "pullType" => $data['pullType'] ?? 'fifo',
//             "payment" => $data['payment'] ?? null,
//         ]);

//         $total = 0;
//         $profit = 0;
//         $outOfStockProducts = [];

//         foreach ($data['products'] as $productData) {
//             $product = Product::findOrFail($productData['id']);
//             $quantityNeeded = $productData['quantity'];
//             $sellingPrice = $product->sellingPrice;

//             if ($data['pullType'] === 'fifo') {
//                 // ✅ FIFO
//                 $shipments = ShipmentProduct::where('product_id', $product->id)
//                     ->where('quantity', '>', 0)
//                     ->orderBy('id', 'asc')
//                     ->get();

//                 foreach ($shipments as $shipmentProduct) {
//                     if ($quantityNeeded <= 0) break;

//                     $takeQty = min($shipmentProduct->quantity, $quantityNeeded);

//                     $shipmentProduct->decrement('quantity', $takeQty);

//                     $lineTotal = $sellingPrice * $takeQty;
//                     $lineCost = $shipmentProduct->unitPrice * $takeQty;
//                     $lineProfit = $lineTotal - $lineCost;

//                     $total += $lineTotal;
//                     $profit += $lineProfit;
//                     $quantityNeeded -= $takeQty;

//                     $invoice->products()->attach($product->id, [
//                         'quantity' => $takeQty,
//                         'total' => $lineTotal,
//                         'profit' => $lineProfit,
//                         'shipment_id' => $shipmentProduct->shipment_id,
//                     ]);

//                     if ($shipmentProduct->quantity - $takeQty == 0) {
//                         $outOfStockProducts[] = $product->name . " (Shipment ID: " . $shipmentProduct->shipment_id . ")";
//                     }
//                 }

//                 if ($quantityNeeded > 0) {
//                     throw new \Exception("الكمية المطلوبة من {$product->name} غير متاحة.");
//                 }

//             } else {
//                 // ✅ Manual
//                 if (!isset($productData['shipment_id'])) {
//                     throw new \Exception("يجب تحديد الشحنة عند اختيار manual pull للمنتج {$product->name}");
//                 }

//                 $shipmentProduct = ShipmentProduct::where('product_id', $product->id)
//                     ->where('shipment_id', $productData['shipment_id'])
//                     ->first();

//                 if (!$shipmentProduct) {
//                     throw new \Exception("الشحنة المختارة لا تخص المنتج {$product->name}");
//                 }

//                 if ($shipmentProduct->quantity < $quantityNeeded) {
//                     throw new \Exception("الكمية غير متاحة في الشحنة المختارة لمنتج {$product->name}");
//                 }

//                 $shipmentProduct->decrement('quantity', $quantityNeeded);

//                 $lineTotal = $sellingPrice * $quantityNeeded;
//                 $lineCost = $shipmentProduct->unitPrice * $quantityNeeded;
//                 $lineProfit = $lineTotal - $lineCost;

//                 $total += $lineTotal;
//                 $profit += $lineProfit;

//                 $invoice->products()->attach($product->id, [
//                     'quantity' => $quantityNeeded,
//                     'total' => $lineTotal,
//                     'profit' => $lineProfit,
//                     'shipment_id' => $shipmentProduct->shipment_id,
//                 ]);

//                 if ($shipmentProduct->quantity - $quantityNeeded == 0) {
//                     $outOfStockProducts[] = $product->name . " (Shipment ID: " . $shipmentProduct->shipment_id . ")";
//                 }
//             }
//         }

//         $finalPrice = $total - $invoice->discount + $invoice->extraAmount;
//         $remaining = $finalPrice - $invoice->paidAmount;

//         $invoice->update([
//             'totalInvoicePrice' => $total,
//             'invoiceAfterDiscount' => $finalPrice,
//             'profit' => $profit,
//             'remainingAmount' => $remaining > 0 ? $remaining : 0,
//         ]);

//         $invoice->update(['status' => $invoice->invoiceAfterDiscount <= $invoice->paidAmount ? 'completed' : 'indebted']);

//         $invoice->updateInvoiceProductCount();

//         DB::commit();

//         return response()->json([
//             'success' => true,
//             'message' => 'تم إنشاء الفاتورة بنجاح',
//             'invoice' => new InvoiceResource($invoice->load('products')),
//             'warning' => !empty($outOfStockProducts)
//                 ? "The following products are now out of stock: " . implode(', ', $outOfStockProducts)
//                 : null,
//         ]);

//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json([
//             'success' => false,
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }


// public function edit(string $id)
// {
//     $invoice = Invoice::with('products')->find($id);
//     if (!$invoice) {
//         return response()->json([
//             'message' => "Invoice not found."
//         ], 404);
//     }

//     $total = 0;
//     $profit = 0;

//     foreach ($invoice->products as $product) {
//         $total += $product->pivot->total;
//         $profit += $product->pivot->profit;
//     }

//     $finalPrice = $total - ($invoice->discount ?? 0) + ($invoice->extraAmount ?? 0);
//     $remaining = $finalPrice - ($invoice->paidAmount ?? 0);

//     $invoice->update([
//         'totalInvoicePrice'   => $total,
//         'invoiceAfterDiscount'=> $finalPrice,
//         'profit'              => $profit,
//         'remainingAmount'     => $remaining > 0 ? $remaining : 0,
//     ]);

//     return response()->json([
//         'message' => 'Invoice details fetched successfully',
//         'invoice' => new InvoiceResource($invoice->load('products')),
//         'extraAmount' => number_format($invoice->extraAmount ?? 0, 2, '.', ''),
//         'totalInvoicePrice' => number_format($total, 2, '.', ''),
//         'discount' => number_format($invoice->discount ?? 0, 2, '.', ''),
//         'invoiceAfterDiscount' => number_format($finalPrice, 2, '.', ''),
//         'warning' => null,
//     ]);
// }



// public function update(InvoiceRequest $request, Invoice $invoice)
// {
//     DB::beginTransaction();
//     try {
//         $data = $request->validated();

//         // إعادة الكميات القديمة للمخزون قبل التحديث
//         foreach ($invoice->products as $oldProduct) {
//             $shipmentProduct = ShipmentProduct::where('product_id', $oldProduct->id)
//                 ->where('shipment_id', $oldProduct->pivot->shipment_id)
//                 ->first();

//             if ($shipmentProduct) {
//                 $shipmentProduct->increment('quantity', $oldProduct->pivot->quantity);
//             }
//         }

//         // مسح العلاقات القديمة
//         $invoice->products()->detach();

//         // تحديث بيانات الفاتورة الأساسية
//         $invoice->update([
//             "customerName" => $data['customerName'],
//             "customerPhone" => $data['customerPhone'],
//             "admin_id" => auth()->id(),
//             "discount" => $data['discount'] ?? 0,
//             "extraAmount" => $data['extraAmount'] ?? 0,
//             "paidAmount" => $data['paidAmount'] ?? 0,
//             "pullType" => $data['pullType'] ?? $invoice->pullType,
//             "payment" => $data['payment'] ?? $invoice->payment,
//         ]);

//         $total = 0;
//         $profit = 0;
//         $outOfStockProducts = [];

//         foreach ($data['products'] as $productData) {
//             $product = Product::findOrFail($productData['id']);
//             $quantityNeeded = $productData['quantity'];
//             $sellingPrice = $product->sellingPrice;

//             if ($data['pullType'] === 'fifo') {
//                 // ✅ FIFO
//                 $shipments = ShipmentProduct::where('product_id', $product->id)
//                     ->where('quantity', '>', 0)
//                     ->orderBy('id', 'asc')
//                     ->get();

//                 foreach ($shipments as $shipmentProduct) {
//                     if ($quantityNeeded <= 0) break;

//                     $takeQty = min($shipmentProduct->quantity, $quantityNeeded);
//                     $shipmentProduct->decrement('quantity', $takeQty);

//                     $lineTotal = $sellingPrice * $takeQty;
//                     $lineCost = $shipmentProduct->unitPrice * $takeQty;
//                     $lineProfit = $lineTotal - $lineCost;

//                     $total += $lineTotal;
//                     $profit += $lineProfit;
//                     $quantityNeeded -= $takeQty;

//                     $invoice->products()->attach($product->id, [
//                         'quantity' => $takeQty,
//                         'total' => $lineTotal,
//                         'profit' => $lineProfit,
//                         'shipment_id' => $shipmentProduct->shipment_id,
//                     ]);

//                     if ($shipmentProduct->quantity - $takeQty == 0) {
//                         $outOfStockProducts[] = $product->name . " (Shipment ID: " . $shipmentProduct->shipment_id . ")";
//                     }
//                 }

//                 if ($quantityNeeded > 0) {
//                     throw new \Exception("الكمية المطلوبة من {$product->name} غير متاحة.");
//                 }

//             } else {
//                 // ✅ Manual
//                 if (!isset($productData['shipment_id'])) {
//                     throw new \Exception("يجب تحديد الشحنة عند اختيار manual pull للمنتج {$product->name}");
//                 }

//                 $shipmentProduct = ShipmentProduct::where('product_id', $product->id)
//                     ->where('shipment_id', $productData['shipment_id'])
//                     ->first();

//                 if (!$shipmentProduct) {
//                     throw new \Exception("الشحنة المختارة لا تخص المنتج {$product->name}");
//                 }

//                 if ($shipmentProduct->quantity < $quantityNeeded) {
//                     throw new \Exception("الكمية غير متاحة في الشحنة المختارة لمنتج {$product->name}");
//                 }

//                 $shipmentProduct->decrement('quantity', $quantityNeeded);

//                 $lineTotal = $sellingPrice * $quantityNeeded;
//                 $lineCost = $shipmentProduct->unitPrice * $quantityNeeded;
//                 $lineProfit = $lineTotal - $lineCost;

//                 $total += $lineTotal;
//                 $profit += $lineProfit;

//                 $invoice->products()->attach($product->id, [
//                     'quantity' => $quantityNeeded,
//                     'total' => $lineTotal,
//                     'profit' => $lineProfit,
//                     'shipment_id' => $shipmentProduct->shipment_id,
//                 ]);

//                 if ($shipmentProduct->quantity - $quantityNeeded == 0) {
//                     $outOfStockProducts[] = $product->name . " (Shipment ID: " . $shipmentProduct->shipment_id . ")";
//                 }
//             }
//         }

//         $finalPrice = $total - $invoice->discount + $invoice->extraAmount;
//         $remaining = $finalPrice - $invoice->paidAmount;

//         $invoice->update([
//             'totalInvoicePrice' => $total,
//             'invoiceAfterDiscount' => $finalPrice,
//             'profit' => $profit,
//             'remainingAmount' => $remaining > 0 ? $remaining : 0,
//         ]);

//         $invoice->update(['status' => $invoice->invoiceAfterDiscount <= $invoice->paidAmount ? 'completed' : 'indebted']);

//         $invoice->updateInvoiceProductCount();

//         DB::commit();

//         return response()->json([
//             'success' => true,
//             'message' => 'تم تعديل الفاتورة بنجاح',
//             'invoice' => new InvoiceResource($invoice->load('products')),
//             'warning' => !empty($outOfStockProducts)
//                 ? "The following products are now out of stock: " . implode(', ', $outOfStockProducts)
//                 : null,
//         ]);

//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json([
//             'success' => false,
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }



public function updatePaidAmount(UpdatePaidAmountRequest $request, $id)
{

    $Invoice = Invoice::findOrFail($id);
    // $this->authorize('updatePaidAmount',$Invoice);
    $paidAmount = $request->paidAmount;

    if ($paidAmount > $Invoice->remainingAmount) {
        return response()->json([
            'message' => 'المبلغ المدفوع يتجاوز المبلغ المتبقي.',
        ], 400);
    }

    $Invoice->paidAmount += $paidAmount;

    $remainingAmount = $Invoice->invoiceAfterDiscount - $Invoice->paidAmount;
    $Invoice->remainingAmount = $remainingAmount;

    if ($remainingAmount <= 0) {
        $Invoice->status = 'completed';
    } else {
        $Invoice->status = 'indebted';
    }

    $Invoice->save();

    return response()->json([
        'message' => 'تم تحديث المبلغ المدفوع بنجاح.',
        'data' => new InvoiceResource($Invoice),
    ]);
}


  public function destroy(string $id)
  {
      return $this->destroyModel(Invoice::class, InvoiceResource::class, $id);
  }

  public function showDeleted()
  {
    $this->authorize('manage_users');
$Invoices=Invoice::onlyTrashed()->get();
return response()->json([
    'data' =>InvoiceResource::collection($Invoices),
    'message' => "Show Deleted Invoices Successfully."
]);

}

public function restore(string $id)
{
   $this->authorize('manage_users');
$Invoice = Invoice::withTrashed()->where('id', $id)->first();
if (!$Invoice) {
    return response()->json([
        'message' => "Invoice not found."
    ], 404);
}
$Invoice->restore();
return response()->json([
    'data' =>new InvoiceResource($Invoice),
    'message' => "Restore Invoice By Id Successfully."
]);
}

  public function forceDelete(string $id)
  {
      return $this->forceDeleteModel(Invoice::class, $id);
  }



}
