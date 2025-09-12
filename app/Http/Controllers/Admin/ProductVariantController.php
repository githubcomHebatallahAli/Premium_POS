<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductVariantRequest;
use App\Http\Resources\Admin\ProductResource;
use App\Http\Resources\Admin\ProductVariantResource;
use App\Http\Resources\Admin\ShowAllProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductService;
use App\Traits\ManagesModelsTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class ProductVariantController extends Controller
{
    use ManagesModelsTrait;


    public function destroy(string $id){

    return $this->destroyModel(Product::class, ProductResource::class, $id);
    }

    public function showDeleted(){
        $this->authorize('manage_users');
    $Products=Product::onlyTrashed()->get();
    return response()->json([
        'data' =>ProductResource::collection($Products),
        'message' => "Show Deleted Products Successfully."
    ]);
    }

    public function restore(string $id)
    {
       $this->authorize('manage_users');
    $Product = Product::withTrashed()->where('id', $id)->first();
    if (!$Product) {
        return response()->json([
            'message' => "Product not found."
        ], 404);
    }
    $Product->restore();
    return response()->json([
        'data' =>new ProductVariantResource($Product),
        'message' => "Restore ProductVariant By Id Successfully."
    ]);
    }

    public function forceDelete(string $id){

        return $this->forceDeleteModel(ProductVariant::class, $id);
    }

        private function generateVariantSku(Product $product, array $variantData): string
    {
        $base = $product->name;
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
}
