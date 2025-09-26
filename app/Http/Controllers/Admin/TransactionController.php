<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TransactionRequest;
use App\Http\Resources\Admin\ShowAllTransactionResource;
use App\Http\Resources\Admin\TransactionResource;
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


    public function create(TransactionRequest $request)
    {
        // $this->authorize('create',Transaction::class);
           $Transaction =Transaction::create ([
                "type" => $request->type,
                'admin_id'=> auth()->id(),
                "purpose_id" => $request->purpose_id,
                "amount" => $request->amount,
                "remainingAmount" => $request->amount,
                "description" => $request->description,
                'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);

           return response()->json([
            'data' =>new TransactionResource($Transaction),
            'message' => "Transaction Created Successfully."
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

        public function update(TransactionRequest $request, string $id)
        {
            // $this->authorize('manage_users');
           $Transaction =Transaction::findOrFail($id);

           if (!$Transaction) {
            return response()->json([
                'message' => "Transaction not found."
            ], 404);
        }
        // $this->authorize('update',$Transaction);
           $Transaction->update([
            "type" => $request->type,
            'admin_id'=> auth()->id(),
            "purpose_id" => $request->purpose_id,
            "amount" => $request->amount,
            "remainingAmount" => $request->amount,
            "description" => $request->description,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);
           $Transaction->save();
           return response()->json([
            'data' =>new TransactionResource($Transaction),
            'message' => "Update Transaction By Id Successfully."
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
