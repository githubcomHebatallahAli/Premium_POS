<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyRequest;
use App\Http\Resources\Admin\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function showAll()
    {
        // $this->authorize('showAllCat',Company::class);

        $Company = Company::get();

                  return response()->json([
                      'data' =>  CompanyResource::collection($Company),
                      'message' => "Show All Company  With Products."
                  ]);
    }


    public function create(CompanyRequest $request)
    {
        // $this->authorize('create',Company::class);
           $Company =Company::create ([
                "name" => $request->name,
                'address' => $request->address,
                'logo' => $request->logo,
                'firstPhone' => $request->firstPhone,
                'secondPhone' => $request->secondPhone,
            ]);

           return response()->json([
            'data' =>new CompanyResource($Company),
            'message' => "Company Created Successfully."
        ]);
        }

        public function edit(string $id)
        {
            // $this->authorize('manage_users');
        $Company = Company::find($id);
    
            if (!$Company) {
                return response()->json([
                    'message' => "Company not found."
                ], 404);
            }

            // $this->authorize('edit',$Company);

            return response()->json([
                'data' => new CompanyResource($Company),
                'message' => "Edit Company Successfully."
            ]);
        }

        public function update(CompanyRequest $request, string $id)
        {
            $this->authorize('manage_users');
           $Company =Company::findOrFail($id);

           if (!$Company) {
            return response()->json([
                'message' => "Company not found."
            ], 404);
        }
        // $this->authorize('update',$Company);
           $Company->update([
            "name" => $request->name,
            'address' => $request->address,
            'logo' => $request->logo,
            'firstPhone' => $request->firstPhone,
            'secondPhone' => $request->secondPhone,
            ]);

           $Company->save();
           return response()->json([
            'data' =>new CompanyResource($Company),
            'message' => " Update Company By Id Successfully."
        ]);
    }

    public function destroy($id)
{
    $company = Company::findOrFail($id);
    $company->delete();

    return response()->json([
        'message' => 'Company deleted successfully',
    ]);
}

}
