<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImageRequest;
use App\Http\Resources\Admin\ImageResource;
use App\Models\Image;
use App\Traits\ManagesModelsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    use ManagesModelsTrait;
        public function showAll(Request $request)
    {
        // $this->authorize('showAll',Image::class);
        $searchTerm = $request->input('search', '');

        $Image = Image::where('name', 'like', '%' . $searchTerm . '%')        
        ->orderBy('created_at', 'desc')
        ->paginate(10);

                  return response()->json([
                      'data' =>  ImageResource::collection($Image),
                      'pagination' => [
                        'total' => $Image->total(),
                        'count' => $Image->count(),
                        'per_page' => $Image->perPage(),
                        'current_page' => $Image->currentPage(),
                        'total_pages' => $Image->lastPage(),
                        'next_page_url' => $Image->nextPageUrl(),
                        'prev_page_url' => $Image->previousPageUrl(),
                    ],
                      'message' => "Show All Image  With Products."
                  ]);
    }

    public function showAllImage()
    {
        // $this->authorize('showAllCat',Image::class);

        $Image = Image::get();

                  return response()->json([
                      'data' =>  ImageResource::collection($Image),
                      'message' => "Show All Image."
                  ]);
    }


    public function create(ImageRequest $request)
    {
        // $this->authorize('create',Image::class);
           $Image =Image::create ([
                "name" => $request->name,
                'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);
                if ($request->hasFile('image')) {
                 $imagePath = $request->file('image')->store(Image::storageFolder);
                 $Image->image = $imagePath;
                }
                  $Image->save();

           return response()->json([
            'data' =>new ImageResource($Image),
            'message' => "Image Created Successfully."
        ]);
        }


        public function edit(string $id)
        {
            // $this->authorize('manage_users');
        $Image = Image::find($id);

            if (!$Image) {
                return response()->json([
                    'message' => "Image not found."
                ], 404);
            }

            // $this->authorize('edit',$Image);
            return response()->json([
                'data' => new ImageResource($Image),
                'message' => "Edit Image By ID Successfully."
            ]);
        }

        public function update(ImageRequest $request, string $id)
        {
            $this->authorize('manage_users');
           $Image =Image::findOrFail($id);

           if (!$Image) {
            return response()->json([
                'message' => "Image not found."
            ], 404);
        }
        // $this->authorize('update',$Image);
           $Image->update([
            "name" => $request->name,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
            ]);
                   if ($request->hasFile('image')) {
                if ($Image->image) {
                    Storage::disk('public')->delete( $Image->image);
                }
                $imagePath = $request->file('image')->store('Images', 'public');
                 $Image->image = $imagePath;
            }

           $Image->save();
           return response()->json([
            'data' =>new ImageResource($Image),
            'message' => " Update Image By Id Successfully."
        ]);
    }

    public function destroy(string $id){

    return $this->destroyModel(Image::class, ImageResource::class, $id);
    }

    public function showDeleted(){
        $this->authorize('manage_users');
    $Images=Image::onlyTrashed()->get();
    return response()->json([
        'data' =>ImageResource::collection($Images),
        'message' => "Show Deleted Images Successfully."
    ]);
    }

    public function restore(string $id)
    {
       $this->authorize('manage_users');
    $Image = Image::withTrashed()->where('id', $id)->first();
    if (!$Image) {
        return response()->json([
            'message' => "Image not found."
        ], 404);
    }
    $Image->restore();
    return response()->json([
        'data' =>new ImageResource($Image),
        'message' => "Restore Image By Id Successfully."
    ]);
    }

    public function forceDelete(string $id){

        return $this->forceDeleteModel(Image::class, $id);
    }

}
