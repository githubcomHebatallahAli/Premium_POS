<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompanyRequest;
use App\Http\Resources\Admin\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function showAll()
    {
        $this->authorize('manage_users');
        // $this->authorize('showAllCat',Company::class);
        $Company = Company::get();

                  return response()->json([
                      'data' =>  CompanyResource::collection($Company),
                      'message' => "Show All Company  With Products."
                  ]);
    }


    public function create(CompanyRequest $request)
    {
        $this->authorize('manage_users');
        // $this->authorize('create',Company::class);
           $Company =Company::create ([
                "name" => $request->name,
                'address' => $request->address,
                'firstPhone' => $request->firstPhone,
                'secondPhone' => $request->secondPhone,
                'commercialNo' => $request->commercialNo,
                'taxNo' => $request->taxNo,
                'admin_id'=> auth()->id(),
                'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),

            ]);
               if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store(Company::storageFolder);
                $Company->logo = $logoPath;
            }
              $Company->save();

           return response()->json([
            'data' =>new CompanyResource($Company),
            'message' => "Company Created Successfully."
        ]);
        }

     public function edit()
{
    $admin = auth('admin')->user(); 
    $company = $admin->company;

    if (!$company) {
        return response()->json([
            'message' => "Company not found."
        ], 404);
    }

    return response()->json([
        'data' => new CompanyResource($company),
        'message' => "Company Retrieved Successfully."
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
            'firstPhone' => $request->firstPhone,
            'secondPhone' => $request->secondPhone,
            'commercialNo' => $request->commercialNo,
            'taxNo' => $request->taxNo,
            'admin_id'=> auth()->id(),
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);
                 if ($request->hasFile('logo')) {
                if ($Company->logo) {
                    Storage::disk('public')->delete( $Company->logo);
                }
                $logoPath = $request->file('logo')->store('Company', 'public');
                 $Company->logo = $logoPath;
            }

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
