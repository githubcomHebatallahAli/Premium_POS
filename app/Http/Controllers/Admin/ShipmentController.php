<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShipmentRequest;
use App\Http\Requests\Admin\UpdatePaidAmountRequest;
use App\Http\Resources\Admin\ShipmentResource;
use App\Models\Shipment;
use App\Models\Supplier;
use App\Services\ShipmentService;
use App\Traits\ManagesModelsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    use ManagesModelsTrait;
    
    public function __construct(private ShipmentService $shipmentService) {}

    public function create(ShipmentRequest $request)
    {
        $shipment = $this->shipmentService->create($request->validated());

        return response()->json([
            'message' => 'Shipment created successfully',
            'data' => new ShipmentResource($shipment),
        ]);
    }

    public function update(ShipmentRequest $request, $id)
    {
        $shipment = Shipment::findOrFail($id);
        $updated = $this->shipmentService->update($shipment, $request->validated());

        return response()->json([
            'message' => 'Shipment updated successfully',
            'data' => new ShipmentResource($updated),
        ]);
    }

public function edit($id)
{
    $shipment = Shipment::with(['products', 'supplier'])->findOrFail($id);
    $this->shipmentService->calculateTotals($shipment);

    return response()->json([
        'message' => 'Shipment fetched successfully',
        'data' => new ShipmentResource($shipment),
    ]);
}

public function showAll(Request $request)
{
    $searchTerm = $request->input('search', '');
    $status = $request->input('status', null);
    $supplierId = $request->input('supplier_id', null);
    $fromDate = $request->input('from_date');
    $toDate = $request->input('to_date');

    $query = Shipment::with('supplier')
        ->when($searchTerm, function($q) use ($searchTerm) {
            $q->where(function($sub) use ($searchTerm) {
                $sub->where('importer', 'like', "%{$searchTerm}%")
                    ->orWhereHas('supplier', function($supplierQ) use ($searchTerm) {
                        $supplierQ->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        })
        ->when($status, fn($q) => $q->where('status', $status))
        ->when($supplierId, fn($q) => $q->where('supplier_id', $supplierId))
        ->when($fromDate, fn($q) => $q->whereDate('creationDate', '>=', $fromDate))
        ->when($toDate, fn($q) => $q->whereDate('creationDate', '<=', $toDate))
        ->orderBy('created_at', 'desc');

    $shipments = $query->paginate(10);

    // الإحصائيات - نفس الفاتورة بالظبط
    $paidAmount = (clone $query)->sum('paidAmount');
    $remainingAmount = (clone $query)->where('status', 'indebted')->sum('remainingAmount');
    $totalPurchases = (clone $query)->where('status', 'completed')->sum('invoiceAfterDiscount');

    return response()->json([
        'data' => $shipments->map(function ($shipment) {
            return [
                'id' => $shipment->id,
                'supplier_name' => $shipment->supplier->name,
                'importer' => $shipment->importer,
                'invoiceAfterDiscount' => $shipment->invoiceAfterDiscount,
                'creationDate' => $shipment->creationDate,
                'remainingAmount' => $shipment->remainingAmount,
                'status' => $shipment->status,
            ];
        }),
        'pagination' => [
            'total' => $shipments->total(),
            'count' => $shipments->count(),
            'per_page' => $shipments->perPage(),
            'current_page' => $shipments->currentPage(),
            'total_pages' => $shipments->lastPage(),
        ],
        'statistics' => [
            'paid_amount' => number_format($paidAmount, 2),
            'remaining_amount' => number_format($remainingAmount, 2),
            'total_purchases' => number_format($totalPurchases, 2),
        ],
        'message' => "Show All Shipments Successfully."
    ]);
}

public function updatePaidAmount(UpdatePaidAmountRequest $request, $id)
{
    $shipment = Shipment::findOrFail($id);
    $paidAmount = $request->paidAmount;

    if ($paidAmount > $shipment->remainingAmount) {
        return response()->json([
            'message' => 'المبلغ المدفوع يتجاوز المبلغ المتبقي.',
        ], 400);
    }

    $shipment->paidAmount += $paidAmount;
    $remainingAmount = $shipment->invoiceAfterDiscount - $shipment->paidAmount;
    $shipment->remainingAmount = $remainingAmount;

    // تحديث الحالة - نفس الفاتورة بالظبط
    if ($remainingAmount <= 0) {
        $shipment->status = 'completed'; // مدفوع بالكامل
    } else {
        $shipment->status = 'indebted'; // غير مكتمل الدفع
    }

    $shipment->save();

    return response()->json([
        'message' => 'تم تحديث المبلغ المدفوع بنجاح.',
        'data' => new ShipmentResource($shipment),
    ]);
}

    public function destroy(string $id)
    {
        return $this->destroyModel(Shipment::class, ShipmentResource::class, $id);
    }

    public function showDeleted()
    {
        $shipments = Shipment::onlyTrashed()->with('supplier')->get();
        return response()->json([
            'data' => ShipmentResource::collection($shipments),
            'message' => "Show Deleted Shipments Successfully."
        ]);
    }

    public function restore(string $id)
    {
        $shipment = Shipment::withTrashed()->where('id', $id)->first();
        if (!$shipment) {
            return response()->json(['message' => "Shipment not found."], 404);
        }
        $shipment->restore();
        return response()->json([
            'data' => new ShipmentResource($shipment),
            'message' => "Restore Shipment Successfully."
        ]);
    }

    public function forceDelete(string $id)
    {
        return $this->forceDeleteModel(Shipment::class, $id);
    }

    public function fullReturn($id)
{
    $shipment = Shipment::findOrFail($id);

    $returned = $this->shipmentService->fullReturn($shipment);

    return response()->json([
        'message' => 'Shipment fully returned',
        'data' => new ShipmentResource($returned),
    ]);
}

public function partialReturn(Request $request, $id)
{
    $shipment = Shipment::findOrFail($id);

    $returned = $this->shipmentService->partialReturn($shipment, $request->input('products', []));

    return response()->json([
        'message' => 'Shipment partially returned',
        'data' => new ShipmentResource($returned),
    ]);
}
}

//     public function showAll(Request $request)
// {
//     $this->authorize('showAll', Shipment::class);

//     $searchTerm = $request->input('search', '');
//     $statusFilter = $request->input('status', '');

//     // $query = Shipment::where('supplierName', 'like', '%' . $searchTerm . '%');
//     $query = Shipment::with('supplier')
//     ->when($searchTerm, function ($query) use ($searchTerm) {
//         $query->whereHas('supplier', function ($q) use ($searchTerm) {
//             $q->where('supplierName', 'like', '%' . $searchTerm . '%');
//         });
//     });

//   if ($request->filled('status') && in_array($statusFilter, ['pending', 'paid', 'partialReturn', 'return'])) {
//         $query->where('status', $statusFilter);
//     }

//     $shipments = $query->orderBy('created_at', 'desc')
//                       ->paginate(10);

//     $paidAmount = Shipment::sum('paidAmount');
//     $remainingAmount = Shipment::where('status', 'pending')->sum('remainingAmount');

//     return response()->json([
//         'data' => ShipmentResource::collection($shipments),
//         'pagination' => [
//             'total' => $shipments->total(),
//             'count' => $shipments->count(),
//             'per_page' => $shipments->perPage(),
//             'current_page' => $shipments->currentPage(),
//             'total_pages' => $shipments->lastPage(),
//             'next_page_url' => $shipments->nextPageUrl(),
//             'prev_page_url' => $shipments->previousPageUrl()
//         ],
//         'statistics' => [
//             'paid_amount' => number_format($paidAmount, 2, '.', ''),
//             'remaining_amount' => number_format($remainingAmount, 2, '.', ''),
//         ],
//         'message' => "Show All Shipment."
//     ]);
// }


