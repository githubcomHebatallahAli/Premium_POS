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


class ProductVariantController extends Controller
{
    use ManagesModelsTrait;
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function create(ProductVariantRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->createProduct(
                $request->validated()
            );

            return response()->json([
                'data' => new ProductResource($product),
                'message' => 'Product created successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating product: ' . $e->getMessage()
            ], 500);
        }
    }

   public function update(ProductVariantRequest $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $updatedProduct = $this->productService->updateProduct(
                $product,
                $request->validated()
            );

            return response()->json([
                'data' => new ProductResource($updatedProduct),
                'message' => 'Product updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating product: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function showAll(Request $request)
    {
        $this->authorize('manage_users');

        $products = $this->productService->getAllProducts($request);

        return response()->json([
            'data' => ShowAllProductResource::collection($products),
            'pagination' => [
                'total' => $products->total(),
                'count' => $products->count(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'total_pages' => $products->lastPage(),
                'next_page_url' => $products->nextPageUrl(),
                'prev_page_url' => $products->previousPageUrl(),
            ],
            'message' => "Show All Products."
        ]);
    }

    public function showAllProductVariant()
    {
        $this->authorize('manage_users');

        $variants = $this->productService->getAllProductVariants();

        return response()->json([
            'data' => ShowAllProductResource::collection($variants),
            'message' => "Show All ProductVariants."
        ]);
    }

        public function showProductVariantLessThan5()
    {
        $this->authorize('manage_users');

        $variants = $this->productService->getLowStockVariants();

        return response()->json([
            'data' => ShowAllProductResource::collection($variants),
            'message' => "Show All ProductVariants with quantity <= 5."
        ]);
    }




    public function edit(string $id)
    {
        $this->authorize('manage_users');

        $product = $this->productService->getProductById($id);

        if (!$product) {
            return response()->json([
                'message' => "Product not found."
            ], 404);
        }

        return response()->json([
            'data' => new ProductResource($product),
            'message' => "Edit Product By ID Successfully."
        ]);
    }

    public function destroy(string $id){

    return $this->destroyModel(Product::class, ProductResource::class, $id);
    }

    public function showDeleted()
    {
        $this->authorize('manage_users');

        $products = $this->productService->getDeletedProducts();

        return response()->json([
            'data' => ProductResource::collection($products),
            'message' => "Show Deleted Products Successfully."
        ]);
    }

    public function restore(string $id)
    {
        $this->authorize('manage_users');

        $product = $this->productService->restoreProduct($id);

        if (!$product) {
            return response()->json([
                'message' => "Product not found."
            ], 404);
        }

        return response()->json([
            'data' => new ProductResource($product),
            'message' => "Restore Product By Id Successfully."
        ]);
    }

    public function forceDelete(string $id){

        return $this->forceDeleteModel(Product::class, $id);
    }
}
