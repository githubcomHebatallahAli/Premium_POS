<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Http\Resources\Admin\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Traits\ManagesModelsTrait;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class ProductVariantController extends Controller
{
    use ManagesModelsTrait;
        public function create(ProductVariantRequest $request)
    {
        $this->authorize('manage_users');
        // $this->authorize('create',ProductVariant::class);
        $formattedSellingPrice = number_format($request->sellingPrice, 2, '.', '');
         $product = Product::findOrFail($request->product_id);
      
           $ProductVariant =ProductVariant::create ([
                "product_id" => $request->product_id,
                "sellingPrice" => $formattedSellingPrice,
                'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'),
                'color' => $request->color,
                'size' => $request->size,
                'clothes' => $request->clothes,
                'barcode' => $request->barcode,
                'sku' => $this->generateVariantSku($product->name, $request->all()),
                'notes' => $request->notes,
            ]);

           return response()->json([
            'data' =>new ProductVariantResource($ProductVariant),
            'message' => "Variant Created Successfully."
        ]);
        }

    private function generateVariantSku(string $productName, array $variantData): string
    {
        $base = $productName;
        $color = $variantData['color'] ?? null;
        $size = $variantData['size'] ?? null;
        $clothes = $variantData['clothes'] ?? null;
        $random = Str::upper(Str::random(4));

        $skuParts = [$base];

        if ($color) $skuParts[] = $color;
        if ($size) $skuParts[] = $size;
        if ($clothes) $skuParts[] = $clothes;

        return implode('-', $skuParts) . '-' . $random;
    }

        public function edit(string $id)
        {
            $this->authorize('manage_users');
            $ProductVariant = ProductVariant::with(['product'])->find($id);

            if (!$ProductVariant) {
                return response()->json([
                    'message' => "Variant not found."
                ], 404);
            }

            // $this->authorize('edit',$ProductVariant);
            return response()->json([
                'data' => new ProductVariantResource($ProductVariant),
                'message' => "Edit Variant By ID Successfully."
            ]);
        }

public function update(ProductVariantRequest $request, string $id)
{
    $this->authorize('manage_users');

    $variant = ProductVariant::findOrFail($id);

    $product = Product::findOrFail($variant->product_id);
    $formattedSellingPrice = number_format($request->sellingPrice, 2, '.', '');

    $updateData = [
        "product_id" => $request->poduct_id,
        "sellingPrice" => $formattedSellingPrice,
        'color' => $request->color,
        'size' => $request->size,
        'clothes' => $request->clothes,
        'barcode' => $request->barcode,
        'notes' => $request->notes,
    ];

    
    if (
        $product->name !== $variant->product->name ||
        $request->color !== $variant->color ||
        $request->size !== $variant->size ||
        $request->clothes !== $variant->clothes
    ) {
        $updateData['sku'] = $this->generateVariantSku($product->name, $request->all());
    } else {
        $updateData['sku'] = $variant->sku; 
    }


    $variant->update($updateData);

    return response()->json([
        'data' => new ProductVariantResource($variant),
        'message' => "Variant Updated Successfully."
    ]);
}



    public function destroy(string $id){

    return $this->destroyModel(ProductVariant::class, ProductVariantResource::class, $id);
    }

    public function showDeleted(){
        $this->authorize('manage_users');
    $ProductVariants=ProductVariant::onlyTrashed()->get();
    return response()->json([
        'data' =>ProductVariantResource::collection($ProductVariants),
        'message' => "Show Deleted Variants Successfully."
    ]);
    }

    public function restore(string $id)
    {
       $this->authorize('manage_users');
    $ProductVariant = ProductVariant::withTrashed()->where('id', $id)->first();
    if (!$ProductVariant) {
        return response()->json([
            'message' => "Variant not found."
        ], 404);
    }
    $ProductVariant->restore();
    return response()->json([
        'data' =>new ProductVariantResource($ProductVariant),
        'message' => "Restore ProductVariantVariant By Id Successfully."
    ]);
    }

    public function forceDelete(string $id){

        return $this->forceDeleteModel(ProductVariant::class, $id);
    }

}
