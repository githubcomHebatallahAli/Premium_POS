<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PurposeRequest;
use App\Http\Resources\Admin\PurposeResource;
use App\Models\Purpose;
use Illuminate\Http\Request;

class PurposeController extends Controller
{
        public function showAll(Request $request)
    {
        // $this->authorize('showAll',Purpose::class);
       

        $Purpose = Purpose::orderBy('created_at', 'desc')
        ->paginate(10);

                  return response()->json([
                      'data' =>  PurposeResource::collection($Purpose),
                      'pagination' => [
                        'total' => $Purpose->total(),
                        'count' => $Purpose->count(),
                        'per_page' => $Purpose->perPage(),
                        'current_page' => $Purpose->currentPage(),
                        'total_pages' => $Purpose->lastPage(),
                        'next_page_url' => $Purpose->nextPageUrl(),
                        'prev_page_url' => $Purpose->previousPageUrl(),
                    ],
                      'message' => "Show All Purpose."
                  ]);
    }

    public function showAllPurpose()
    {
        // $this->authorize('showAllCat',Purpose::class);

        $Purpose = Purpose::get();

                  return response()->json([
                      'data' =>  PurposeResource::collection($Purpose),
                      'message' => "Show All Purpose."
                  ]);
    }


    public function create(PurposeRequest $request)
    {
        // $this->authorize('create',Purpose::class);
           $Purpose =Purpose::create ([
                "transactionReason" => $request->transactionReason,
                'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);

           return response()->json([
            'data' =>new PurposeResource($Purpose),
            'message' => "Purpose Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
            // $this->authorize('manage_users');
        $Purpose = Purpose::find($id);
            if (!$Purpose) {
                return response()->json([
                    'message' => "Purpose not found."
                ], 404);
            }

            // $this->authorize('edit',$Purpose);

            return response()->json([
                'data' => new PurposeResource($Purpose),
                'message' => "Edit Purpose By ID Successfully."
            ]);
        }

        public function update(PurposeRequest $request, string $id)
        {
            $this->authorize('manage_users');
           $Purpose =Purpose::findOrFail($id);

           if (!$Purpose) {
            return response()->json([
                'message' => "Purpose not found."
            ], 404);
        }
        // $this->authorize('update',$Purpose);
           $Purpose->update([
            "transactionReason" => $request->transactionReason,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);

           $Purpose->save();
           return response()->json([
            'data' =>new PurposeResource($Purpose),
            'message' => "Update Purpose By Id Successfully."
        ]);
    }

    public function destroy(string $id){

    return $this->destroyModel(Purpose::class, PurposeResource::class, $id);
    }

    public function showDeleted(){
        $this->authorize('manage_users');
    $Purposes=Purpose::onlyTrashed()->get();
    return response()->json([
        'data' =>PurposeResource::collection($Purposes),
        'message' => "Show Deleted Purposes Successfully."
    ]);
    }

    public function restore(string $id)
    {
       $this->authorize('manage_users');
    $Purpose = Purpose::withTrashed()->where('id', $id)->first();
    if (!$Purpose) {
        return response()->json([
            'message' => "Purpose not found."
        ], 404);
    }
    $Purpose->restore();
    return response()->json([
        'data' =>new PurposeResource($Purpose),
        'message' => "Restore Purpose By Id Successfully."
    ]);
    }

    public function forceDelete(string $id){

        return $this->forceDeleteModel(Purpose::class, $id);
    }
}
