<?php

namespace App\Http\Controllers\Admin;

use App\Models\Dept;
use App\Models\Invoice;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use App\Traits\ManagesModelsTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WithdrawRequest;
use App\Http\Resources\Admin\WithdrawResource;


class WithdrawController extends Controller
{
    use ManagesModelsTrait;
    public function showAll(Request $request)
    {
        $this->authorize('manage_users');

        $searchTerm = $request->input('search', '');

        $Withdraws = Withdraw::where('personName', 'like', '%' . $searchTerm . '%')
                     ->orderBy('created_at', 'desc')
                     ->paginate(10);
        return response()->json([
            'data' => $Withdraws->map(function ($Withdraws) {
                return [
                    'id' => $Withdraws->id,
                    'personName' => $Withdraws-> personName,
                    'withdrawnAmount' => $Withdraws-> withdrawnAmount,
                    'creationDate' => $Withdraws-> creationDate,
                ];
            }),
            'pagination' => [
                'total' => $Withdraws->total(),
                'count' => $Withdraws->count(),
                'per_page' => $Withdraws->perPage(),
                'current_page' => $Withdraws->currentPage(),
                'total_pages' => $Withdraws->lastPage(),
                'next_page_url' => $Withdraws->nextPageUrl(),
                'prev_page_url' => $Withdraws->previousPageUrl(),
            ],
            'message' => "Show All Withdraws Successfully."
        ]);
    }


    // public function create(WithdrawRequest $request)
    // {
    //     $this->authorize('manage_users');

    //     $totalSales = Invoice::sum('invoiceAfterDiscount');

    //     $totalWithdrawals = Withdraw::sum('withdrawnAmount');

    //     $availableWithdrawal = $totalSales - $totalWithdrawals;

    //     $amountToWithdraw = $request->withdrawnAmount;

    //     if ($amountToWithdraw > $availableWithdrawal) {
    //         return response()->json([
    //             'message' => 'المبلغ المطلوب سحبه يتجاوز المبلغ المتاح.',
    //             'availableWithdrawal' => $availableWithdrawal,
    //         ], 400);
    //     }

    //     $remainingAmountAfterWithdraw = $availableWithdrawal - $amountToWithdraw;

    //     $withdraw = Withdraw::create([
    //         'personName' => $request->personName,
    //         'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d h:i:s'),
    //         'withdrawnAmount' => $amountToWithdraw,
    //         'remainingAmount' => $remainingAmountAfterWithdraw,
    //         // 'totalSalesCopy' => $totalSales,
    //         'description' => $request->description,
    //     ]);

    //     return response()->json([
    //         'message' => 'تم السحب بنجاح.',
    //         'data' => new WithdrawResource($withdraw),
    //         'availableWithdrawal' => $remainingAmountAfterWithdraw,
    //     ]);
    // }

    public function create(WithdrawRequest $request)
{
    $this->authorize('manage_users');

    $totalSalesFromInvoices = Invoice::sum('invoiceAfterDiscount');

    $totalSalesFromDepts = Dept::where('status', 'paid')->sum('depetAfterDiscount');

    $totalSales = $totalSalesFromInvoices + $totalSalesFromDepts;

    // إجمالي المبالغ المسحوبة
    $totalWithdrawals = Withdraw::sum('withdrawnAmount');

    // حساب المبلغ المتاح للسحب
    $availableWithdrawal = $totalSales - $totalWithdrawals;

    $amountToWithdraw = $request->withdrawnAmount;

    if ($amountToWithdraw > $availableWithdrawal) {
        return response()->json([
            'message' => 'المبلغ المطلوب سحبه يتجاوز المبلغ المتاح.',
            'availableWithdrawal' => $availableWithdrawal,
        ], 400);
    }

    $remainingAmountAfterWithdraw = $availableWithdrawal - $amountToWithdraw;

    $withdraw = Withdraw::create([
        'personName' => $request->personName,
        'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d h:i:s'),
        'withdrawnAmount' => $amountToWithdraw,
        'remainingAmount' => $remainingAmountAfterWithdraw,
        'description' => $request->description,
    ]);

    return response()->json([
        'message' => 'تم السحب بنجاح.',
        'data' => new WithdrawResource($withdraw),
        'availableWithdrawal' => $remainingAmountAfterWithdraw,
    ]);
}



    public function edit(string $id)
    {
        $this->authorize('manage_users');
        $Withdraw = Withdraw::find($id);

        if (!$Withdraw) {
            return response()->json([
                'message' => "Withdraw not found."
            ], 404);
        }

        return response()->json([
            'data' =>new WithdrawResource($Withdraw),
            'message' => "Edit Withdraw By ID Successfully."
        ]);
    }


    public function update(WithdrawRequest $request, string $id)
    {
        $this->authorize('manage_users');

        $withdraw = Withdraw::find($id);

        if (!$withdraw) {
            return response()->json([
                'message' => 'عملية السحب غير موجودة.',
            ], 404);
        }

        $totalSales = Invoice::sum('invoiceAfterDiscount');
        $totalWithdrawals = Withdraw::where('id', '!=', $id)->sum('withdrawnAmount'); // استبعاد السحب الحالي
        $availableWithdrawal = $totalSales - $totalWithdrawals;

        $amountToWithdraw = $request->withdrawnAmount;

        if ($amountToWithdraw > $availableWithdrawal) {
            return response()->json([
                'message' => 'المبلغ المطلوب سحبه يتجاوز المبلغ المتاح.',
                'availableWithdrawal' => $availableWithdrawal,
            ], 400);
        }

        $remainingAmountAfterWithdraw = $availableWithdrawal - $amountToWithdraw;

        $withdraw->update([
            'personName' => $request->personName,
            'withdrawnAmount' => $amountToWithdraw,
            'remainingAmount' => $remainingAmountAfterWithdraw,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'تم تحديث عملية السحب بنجاح.',
            'data' => new WithdrawResource($withdraw),
            'availableWithdrawal' => $remainingAmountAfterWithdraw,
        ]);
    }



  public function destroy(string $id)
  {
      return $this->destroyModel(Withdraw::class, WithdrawResource::class, $id);
  }

  public function showDeleted()
  {
    $this->authorize('manage_users');
$Withdraws=Withdraw::onlyTrashed()->get();
return response()->json([
    'data' =>WithdrawResource::collection($Withdraws),
    'message' => "Show Deleted Withdraws Successfully."
]);

}

public function restore(string $id)
{
   $this->authorize('manage_users');
$Withdraw = Withdraw::withTrashed()->where('id', $id)->first();
if (!$Withdraw) {
    return response()->json([
        'message' => "Withdraw not found."
    ], 404);
}
$Withdraw->restore();
return response()->json([
    'data' =>new WithdrawResource($Withdraw),
    'message' => "Restore Withdraw By Id Successfully."
]);
}

  public function forceDelete(string $id)
  {
      return $this->forceDeleteModel(Withdraw::class, $id);
  }
}
