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

    $total  = $invoice->products->sum(fn($p) => $p->pivot->total);
    $profit = $invoice->products->sum(fn($p) => $p->pivot->profit);

    // هنا مش محتاجين نعمل update تاني
    $this->invoiceService->calculateTotals($invoice, $total, $profit);

    return response()->json([
        'message' => 'Invoice fetched successfully',
        'data'    => new InvoiceResource($invoice->fresh('products')),
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
            // 'total_profit'      => number_format($totalProfit, 2, '.', ''),
        ],
        'message' => "Show All Invoices Successfully."
    ]);
}

// public function updatePaidAmount(UpdatePaidAmountRequest $request, $id)
// {

//     $Invoice = Invoice::findOrFail($id);
//     // $this->authorize('updatePaidAmount',$Invoice);
//     $paidAmount = $request->paidAmount;

//     if ($paidAmount > $Invoice->remainingAmount) {
//         return response()->json([
//             'message' => 'المبلغ المدفوع يتجاوز المبلغ المتبقي.',
//         ], 400);
//     }

//     $Invoice->paidAmount += $paidAmount;

//     $remainingAmount = $Invoice->invoiceAfterDiscount - $Invoice->paidAmount;
//     $Invoice->remainingAmount = $remainingAmount;

//     if ($remainingAmount <= 0) {
//         $Invoice->status = 'completed';
//     } else {
//         $Invoice->status = 'indebted';
//     }

//     $Invoice->save();

//     return response()->json([
//         'message' => 'تم تحديث المبلغ المدفوع بنجاح.',
//         'data' => new InvoiceResource($Invoice),
//     ]);
// }

public function updatePaidAmount(UpdatePaidAmountRequest $request, $id)
{
    $invoice = Invoice::with('products')->findOrFail($id);
    // $this->authorize('updatePaidAmount', $invoice);

    $paidAmount = $request->paidAmount;

    if ($paidAmount > $invoice->remainingAmount) {
        return response()->json([
            'message' => 'المبلغ المدفوع يتجاوز المبلغ المتبقي.',
        ], 400);
    }

    // تحديث المبلغ المدفوع
    $invoice->paidAmount += $paidAmount;
    $invoice->save();

    // إعادة حساب الإجمالي والربح من المنتجات
    $total  = $invoice->products->sum(fn($p) => $p->pivot->total);
    $profit = $invoice->products->sum(fn($p) => $p->pivot->profit);

    // إعادة استخدام calculateTotals لتحديث باقي الحقول (final, remaining, status...)
    $this->invoiceService->calculateTotals($invoice, $total, $profit);

    return response()->json([
        'message' => 'تم تحديث المبلغ المدفوع بنجاح.',
        'data'    => new InvoiceResource($invoice->fresh('products')),
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
