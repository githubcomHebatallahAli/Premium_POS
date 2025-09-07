<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function createProduct(array $data, $mainImage = null)
    {
        $mainImagePath = $this->uploadMainImage($mainImage);

        $product = Product::create([
            'name' => $data['name'],
            'sellingPrice' => $data['sellingPrice'],
            'mainImage' => $mainImagePath,
            'category_id' => $data['category_id'],
            'brand_id' => $data['brand_id'] ?? null,
            'description' => $data['description'] ?? null,
            'country' => $data['country'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'sku' => $data['sku'] ?? $this->generateProductSku($data['name']),
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s')
        ]);

        $this->handleVariants($product, $data['variants'] ?? []);

        return $product->load(['category', 'brand', 'variants']);
    }

    public function updateProduct(Product $product, array $data, $mainImage = null)
    {
        $updateData = [
            'name' => $data['name'],
            'sellingPrice' => $data['sellingPrice'],
            'category_id' => $data['category_id'],
            'brand_id' => $data['brand_id'] ?? null,
            'description' => $data['description'] ?? null,
            'country' => $data['country'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'sku' => $data['sku'] ?? $product->sku,
        ];

        if ($mainImage) {
            $this->deleteMainImage($product->mainImage);
            $updateData['mainImage'] = $this->uploadMainImage($mainImage);
        }

        $product->update($updateData);

        $product->variants()->delete();
        $this->handleVariants($product, $data['variants'] ?? []);

        return $product->load(['category', 'brand', 'variants']);
    }


    private function createVariant(Product $product, array $variantData)
    {
        $imagesPaths = $this->uploadVariantImages($variantData['images'] ?? []);

        ProductVariant::create([
            'product_id' => $product->id,
            'color' => $variantData['color'] ?? null,
            'size' => $variantData['size'] ?? null,
            'clothes' => $variantData['clothes'] ?? null,
            'sellingPrice' => $variantData['sellingPrice'] ?? $product->sellingPrice,
            'images' => $imagesPaths,
            'sku' => $variantData['sku'] ?? $this->generateVariantSku($product, $variantData),
            'barcode' => $variantData['barcode'] ?? null,
            'notes' => $variantData['notes'] ?? null,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s')
        ]);
    }

    private function uploadMainImage($image)
    {
        if (!$image) return null;
        return $image->store('products/main', 'public');
    }

    private function uploadVariantImages(array $images)
    {
        $paths = [];
        foreach ($images as $image) {
            $paths[] = $image->store('products/variants', 'public');
        }
        return $paths;
    }

    private function deleteMainImage($imagePath)
    {
        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    private function generateProductSku($name)
    {
        return 'PROD-' . Str::upper(Str::substr(Str::slug($name), 0, 6)) . '-' . Str::random(4);
    }

    private function generateVariantSku(Product $product, array $variantData)
    {
        $base = $product->sku ?: $this->generateProductSku($product->name);
        $color = Str::substr($variantData['color'] ?? 'DEF', 0, 3);
        $size = $variantData['size'] ?? 'DEF';
        
        return $base . '-' . Str::upper($color) . '-' . Str::upper($size);
    }

    private function handleVariants(Product $product, array $variants)
{
   
    $product->variants()->delete();
    
    if (empty($variants)) {
      
        $this->createDefaultVariant($product);
        return;
    }

    foreach ($variants as $variantData) {
        $this->createVariant($product, $variantData);
    }
}

private function createDefaultVariant(Product $product)
{

    ProductVariant::create([
        'product_id' => $product->id,
        'sellingPrice' => $product->sellingPrice,
        'sku' => $product->sku . '-DEFAULT',
        'barcode' => $product->barcode,
        'notes' => 'منتج بدون تفرعات',
        'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s')
    ]);
}
}