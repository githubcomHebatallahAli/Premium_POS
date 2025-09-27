<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TransactionRequest;
use App\Http\Resources\Admin\ShowAllTransactionResource;
use App\Http\Resources\Admin\TransactionResource;
use App\Models\Invoice;
use App\Models\SupplierReturn;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
            public function showAll(Request $request)
    {
        // $this->authorize('showAll',Transaction::class);
        $query = Transaction::with(['admin', 'purpose']);

            if ($request->filled('purpose_id')) {
            $query->where('purpose_id', $request->purpose_id);
        }

          if ($request->filled('type')) {
            $query->where('type', $request->type);
    }

        if ($request->filled('from_date')) {
        $query->whereDate('creationDate', '>=', $request->from_date);
    }

    if ($request->filled('to_date')) {
        $query->whereDate('creationDate', '<=', $request->to_date);
    }

        $Transaction = $query->orderBy('created_at', 'desc')
        ->paginate(10);

                  return response()->json([
                      'data' =>ShowAllTransactionResource::collection($Transaction),
                      'pagination' => [
                        'total' => $Transaction->total(),
                        'count' => $Transaction->count(),
                        'per_page' => $Transaction->perPage(),
                        'current_page' => $Transaction->currentPage(),
                        'total_pages' => $Transaction->lastPage(),
                        'next_page_url' => $Transaction->nextPageUrl(),
                        'prev_page_url' => $Transaction->previousPageUrl(),
                    ],
                      'message' => "Show All Transaction."
                  ]);
    }

    public function showAllTransaction()
    {
        // $this->authorize('showAllCat',Transaction::class);

        $Transaction = Transaction::get();

                  return response()->json([
                      'data' => ShowAllTransactionResource::collection($Transaction),
                      'message' => "Show All Transaction."
                  ]);
    }


    // public function create(TransactionRequest $request)
    // {
    //     // $this->authorize('create',Transaction::class);
    //        $Transaction =Transaction::create ([
    //             "type" => $request->type,
    //             'admin_id'=> auth()->id(),
    //             "purpose_id" => $request->purpose_id,
    //             "amount" => $request->amount,
    //             "remainingAmount" => $request->amount,
    //             "description" => $request->description,
    //             'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
    //         ]);

    //        return response()->json([
    //         'data' =>new TransactionResource($Transaction),
    //         'message' => "Transaction Created Successfully."
    //     ]);
    //     }

    public function create(TransactionRequest $request)
{
    // 1. إجمالي المبيعات من الفواتير
    $totalSalesFromInvoices = Invoice::sum('paidAmount');

    // 2. إجمالي الإيداعات
    $totalDeposits = Transaction::where('type', 'deposit')->sum('amount');

    // 3. إجمالي المرتجعات (refund_amount من supplier_returns)
    $totalReturns = SupplierReturn::sum('refund_amount');

    // 4. إجمالي المسحوبات السابقة
    $totalWithdrawals = Transaction::where('type', 'withdraw')->sum('amount');

    // 5. الرصيد المتاح
    $availableBalance = $totalSalesFromInvoices + $totalDeposits + $totalReturns - $totalWithdrawals;

    // 6. التحقق من نوع العملية
    $amount = $request->amount;
    $type = $request->type; // deposit | withdraw

    if ($type === 'withdraw' && $amount > $availableBalance) {
        return response()->json([
            'message' => 'المبلغ المطلوب سحبه يتجاوز الرصيد المتاح.',
            'availableBalance' => $availableBalance,
        ], 400);
    }

    // 7. إنشاء العملية
    $transaction = Transaction::create([
        'type'          => $type,
        'admin_id'      => auth()->id(),
        'purpose_id'    => $request->purpose_id,
        'amount'        => $amount,
        'remainingAmount' => $type === 'withdraw'
                                ? $availableBalance - $amount
                                : $availableBalance + $amount,
        'description'   => $request->description,
        'creationDate'  => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
    ]);

    return response()->json([
        'data' => new TransactionResource($transaction),
        // 'availableBalance' => $transaction->remainingAmount,
        'availableBalance' => number_format($transaction->remainingAmount, 2, '.', ','), 
        'message' => 'تم تنفيذ العملية بنجاح.',
    ]);
}


        public function edit(string $id)
        {
            // $this->authorize('manage_users');
        $Transaction = Transaction::find($id);
            if (!$Transaction) {
                return response()->json([
                    'message' => "Transaction not found."
                ], 404);
            }

            // $this->authorize('edit',$Transaction);

            return response()->json([
                'data' => new TransactionResource($Transaction),
                'message' => "Edit Transaction By ID Successfully."
            ]);
        }

    //     public function update(TransactionRequest $request, string $id)
    //     {
    //         // $this->authorize('manage_users');
    //        $Transaction =Transaction::findOrFail($id);

    //        if (!$Transaction) {
    //         return response()->json([
    //             'message' => "Transaction not found."
    //         ], 404);
    //     }
    //     // $this->authorize('update',$Transaction);
    //        $Transaction->update([
    //         "type" => $request->type,
    //         'admin_id'=> auth()->id(),
    //         "purpose_id" => $request->purpose_id,
    //         "amount" => $request->amount,
    //         "remainingAmount" => $request->amount,
    //         "description" => $request->description,
    //         'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
    //         ]);
    //        $Transaction->save();
    //        return response()->json([
    //         'data' =>new TransactionResource($Transaction),
    //         'message' => "Update Transaction By Id Successfully."
    //     ]);
    // }

    public function update(TransactionRequest $request, string $id)
{
    $transaction = Transaction::findOrFail($id);

    // 1. إجمالي المبيعات من الفواتير
    $totalSalesFromInvoices = Invoice::sum('paidAmount');

    // 2. إجمالي الإيداعات (من جدول transactions نفسه)
    $totalDeposits = Transaction::where('type', 'deposit')->sum('amount');

    // 3. إجمالي المرتجعات (refund_amount من supplier_returns)
    $totalReturns = SupplierReturn::sum('refund_amount');

    // 4. إجمالي المسحوبات السابقة (باستثناء العملية الحالية)
    $totalWithdrawals = Transaction::where('type', 'withdraw')
        ->where('id', '!=', $transaction->id)
        ->sum('amount');

    // 5. الرصيد المتاح
    $availableBalance = $totalSalesFromInvoices + $totalDeposits + $totalReturns - $totalWithdrawals;

    // 6. التحقق من نوع العملية
    $amount = $request->amount;
    $newType = $request->type;
    $oldType = $transaction->type;

    // ✅ Soft Validation: لو المستخدم حاول يغير نوع العملية
    if ($oldType !== $newType) {
        if ($newType === 'withdraw' && $amount > $availableBalance) {
            return response()->json([
                'message' => 'لا يمكن تغيير العملية إلى سحب لأن المبلغ المطلوب يتجاوز الرصيد المتاح.',
                'availableBalance' => $availableBalance,
            ], 400);
        }

        if ($newType === 'deposit' && $amount <= 0) {
            return response()->json([
                'message' => 'مبلغ الإيداع يجب أن يكون أكبر من صفر.',
            ], 400);
        }
    }

    // 7. تحديث العملية
    $transaction->update([
        'type'           => $newType,
        'admin_id'       => auth()->id(),
        'purpose_id'     => $request->purpose_id,
        'amount'         => $amount,
        'remainingAmount'=> $newType === 'withdraw'
                                ? $availableBalance - $amount
                                : $availableBalance + $amount,
        'description'    => $request->description,
        'creationDate'   => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
    ]);

    return response()->json([
        'data' => new TransactionResource($transaction),
        // 'availableBalance' => $transaction->remainingAmount,
        'availableBalance' => number_format($transaction->remainingAmount, 2, '.', ','), 
        'message' => "تم تحديث العملية بنجاح.",
    ]);
}


    public function destroy(string $id){

    return $this->destroyModel(Transaction::class, TransactionResource::class, $id);
    }

    public function showDeleted(){
        $this->authorize('manage_users');
    $Transactions=Transaction::onlyTrashed()->get();
    return response()->json([
        'data' =>TransactionResource::collection($Transactions),
        'message' => "Show Deleted Transactions Successfully."
    ]);
    }

    public function restore(string $id)
    {
       $this->authorize('manage_users');
    $Transaction = Transaction::withTrashed()->where('id', $id)->first();
    if (!$Transaction) {
        return response()->json([
            'message' => "Transaction not found."
        ], 404);
    }
    $Transaction->restore();
    return response()->json([
        'data' =>new TransactionResource($Transaction),
        'message' => "Restore Transaction By Id Successfully."
    ]);
    }

    public function forceDelete(string $id){

        return $this->forceDeleteModel(Transaction::class, $id);
    }
}
