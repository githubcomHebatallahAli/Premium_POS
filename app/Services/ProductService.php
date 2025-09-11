<?php

namespace App\Services;

use App\Models\Image;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    private function attachProductImages(Product $product, array $imageIds): void
    {
        $product->images()->sync($imageIds);
    }

    private function attachVariantImages(ProductVariant $variant, array $imageIds): void
    {
        $variant->images()->sync($imageIds);
    }

    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'name'         => $data['name'],
                'sellingPrice' => $data['sellingPrice'],
                'category_id'  => $data['category_id'],
                'brand_id'     => $data['brand_id'] ?? null,
                'description'  => $data['description'] ?? null,
                'country'      => $data['country'] ?? null,
                'barcode'      => $data['barcode'] ?? null,
                'sku'          => $this->generateProductSku($data['name']),
                'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s')
            ]);

            // صور المنتج
            if (!empty($data['images'])) {
                $imageIds = [];
                foreach ($data['images'] as $imageFile) {
                    $filename = uniqid() . '.' . $imageFile->getClientOriginalExtension();
                    $imageFile->move(public_path('products'), $filename);
                    $image = Image::create(['path' => 'products/' . $filename]);
                    $imageIds[] = $image->id;
                }
                $product->images()->sync($imageIds);
            }

            // الفاريانت
            $this->handleVariantsOnCreate($product, $data['variants'] ?? []);

            return $product->load(['category', 'brand', 'images', 'variants.images']);
        });
    }

    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update([
                'name'         => $data['name'] ?? $product->name,
                'sellingPrice' => $data['sellingPrice'] ?? $product->sellingPrice,
                'category_id'  => $data['category_id'] ?? $product->category_id,
                'brand_id'     => $data['brand_id'] ?? $product->brand_id,
                'description'  => $data['description'] ?? $product->description,
                'country'      => $data['country'] ?? $product->country,
                'barcode'      => $data['barcode'] ?? $product->barcode,
            ]);

            if (!empty($data['images'])) {
                $imageIds = [];
                foreach ($data['images'] as $imageFile) {
                    $filename = uniqid() . '.' . $imageFile->getClientOriginalExtension();
                    $imageFile->move(public_path('products'), $filename);
                    $image = Image::create(['path' => 'products/' . $filename]);
                    $imageIds[] = $image->id;
                }
                $this->attachProductImages($product, $imageIds);
            }

            if (isset($data['variants'])) {
                $this->handleVariantsOnUpdate($product, $data['variants']);
            }

            return $product->load(['category', 'brand', 'images', 'variants.images']);
        });
    }

    private function handleVariantsOnCreate(Product $product, ?array $variants): void
    {
        if (empty($variants)) {
            $this->createDefaultVariant($product);
            return;
        }

        foreach ($variants as $variantData) {
            $this->createVariant($product, $variantData);
        }
    }

    // private function handleVariantsOnUpdate(Product $product, array $variants): void
    // {
      
    //     $sentIds = collect($variants)->pluck('id')->filter()->all();

    //     $product->variants()->whereNotIn('id', $sentIds)->delete();

      
    //     foreach ($variants as $variantData) {
    //         if (!empty($variantData['id'])) {
    //             $variant = ProductVariant::find($variantData['id']);
    //             if (!$variant) {
    //                 throw new ModelNotFoundException("Variant not found: {$variantData['id']}");
    //             }

    //             $variant->update([
    //                 'color'        => $variantData['color'] ?? $variant->color,
    //                 'size'         => $variantData['size'] ?? $variant->size,
    //                 'clothes'      => $variantData['clothes'] ?? $variant->clothes,
    //                 'sellingPrice' => $variantData['sellingPrice'] ?? $variant->sellingPrice,
    //                 'sku'          => $variantData['sku'] ?? $variant->sku,
    //                 'barcode'      => $variantData['barcode'] ?? $variant->barcode,
    //                 'notes'        => $variantData['notes'] ?? $variant->notes,
    //             ]);

    //             if (!empty($variantData['images'])) {
    //                 $imageIds = [];
    //                 foreach ($variantData['images'] as $imageFile) {
    //                     $filename = uniqid() . '.' . $imageFile->getClientOriginalExtension();
    //                     $imageFile->move(public_path('products'), $filename);
    //                     $image = Image::create(['path' => 'products/' . $filename]);
    //                     $imageIds[] = $image->id;
    //                 }
    //                 $this->attachVariantImages($variant, $imageIds);
    //             }
    //         } else {
    //             $this->createVariant($product, $variantData);
    //         }
    //     }
    // }

private function handleVariantsOnUpdate(Product $product, array $variants): void
{
    $sentIds = collect($variants)->pluck('id')->filter()->all();
    $product->variants()->whereNotIn('id', $sentIds)->delete();

    foreach ($variants as $variantData) {
        if (!empty($variantData['id'])) {
            $variant = ProductVariant::find($variantData['id']);
            if (!$variant) {
                throw new ModelNotFoundException("Variant not found: {$variantData['id']}");
            }

            // تحضير مصفوفة التحديث
            $updateData = [
                'color'        => $variantData['color'] ?? $variant->color,
                'size'         => $variantData['size'] ?? $variant->size,
                'clothes'      => $variantData['clothes'] ?? $variant->clothes,
                'sellingPrice' => $variantData['sellingPrice'] ?? $variant->sellingPrice,
                'sku'          => $variantData['sku'] ?? $variant->sku,
                'notes'        => $variantData['notes'] ?? $variant->notes,
            ];

            // معالجة خاصة للباركود: لا يتم التحديث إلا إذا تم إرسال قيمة جديدة وغير فارغة
            if (array_key_exists('barcode', $variantData)) {
                if ($variantData['barcode'] !== null) {
                    // إذا تم إرسال باركود جديد وقيمته ليست null، نستخدمه
                    $updateData['barcode'] = $variantData['barcode'];
                }
                // إذا كان null، لا نفعله شيئًا (أي نحتفظ بالباركود القديم ضمنيًا)
            } else {
                // إذا لم يتم إرسال حقل الباركود أساسًا في البيانات، نحتفظ بالباركود القديم
                $updateData['barcode'] = $variant->barcode;
            }

            $variant->update($updateData);

            // معالجة الصور (إذا وجدت)
            if (!empty($variantData['images'])) {
                $imageIds = [];
                foreach ($variantData['images'] as $imageFile) {
                    $filename = uniqid() . '.' . $imageFile->getClientOriginalExtension();
                    $imageFile->move(public_path('products'), $filename);
                    $image = Image::create(['path' => 'products/' . $filename]);
                    $imageIds[] = $image->id;
                }
                $this->attachVariantImages($variant, $imageIds);
            }
        } else {
            // إنشاء فاريانت جديد إذا لم يكن له ID
            $this->createVariant($product, $variantData);
        }
    }
}

    private function createVariant(Product $product, array $variantData): ProductVariant
    {
        $variant = ProductVariant::create([
            'product_id'   => $product->id,
            'color'        => $variantData['color'] ?? null,
            'size'         => $variantData['size'] ?? null,
            'clothes'      => $variantData['clothes'] ?? null,
            'sellingPrice' => $variantData['sellingPrice'] ?? $product->sellingPrice,
            'sku'          => $variantData['sku'] ?? $this->generateVariantSku($product, $variantData),
            'barcode'      => $variantData['barcode'] ?? null,
            'notes'        => $variantData['notes'] ?? null,
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s')
        ]);

        if (!empty($variantData['images'])) {
            $imageIds = [];
            foreach ($variantData['images'] as $imageFile) {
                $filename = uniqid() . '.' . $imageFile->getClientOriginalExtension();
                $imageFile->move(public_path('products'), $filename);
                $image = Image::create(['path' => 'products/' . $filename]);
                $imageIds[] = $image->id;
            }
            $this->attachVariantImages($variant, $imageIds);
        }

        return $variant;
    }

    private function createDefaultVariant(Product $product): void
    {
        ProductVariant::create([
            'product_id'   => $product->id,
            'sellingPrice' => $product->sellingPrice,
            'sku'          => $product->sku . '-DEFAULT',
            'barcode'      => $product->barcode,
            'notes'        => 'منتج بدون تفرعات',
            'creationDate' => now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s')
        ]);
    }

    private function generateProductSku(string $name): string
    {
        $random = Str::upper(Str::random(4));
        return $name . '-' . $random;
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

    public function getProductById($id): ?Product
    {
        return Product::with(['category', 'brand','images', 'variants.images'])->find($id);
    }

    public function getDeletedProducts()
    {
        return Product::onlyTrashed()->get();
    }

    public function restoreProduct($id): ?Product
    {
        $product = Product::withTrashed()->where('id', $id)->first();
        if (!$product) {
            return null;
        }
        $product->restore();
        return $product;
    }

    public function getAllProducts($request)
    {
        $searchTerm = $request->input('search', '');
        $query = Product::with(['category', 'brand', 'images', 'variants.images'])

            ->where('name', 'like', '%' . $searchTerm . '%');

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('color') || $request->filled('size') || $request->filled('clothes')) {
            $query->whereHas('variants', function ($q) use ($request) {
                if ($request->filled('color')) {
                    $q->where('color', $request->color);
                }
                if ($request->filled('size')) {
                    $q->where('size', $request->size);
                }
                if ($request->filled('clothes')) {
                    $q->where('clothes', $request->clothes);
                }
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function getAllProductVariants()
    {
        return Product::with(['category', 'brand', 'images'])->get();
    }

    public function getLowStockVariants()
    {
        return ProductVariant::with(['product.category', 'product.brand', 'images'])
            ->where('quantity', '<=', 5)
            ->get();
    }
}
     