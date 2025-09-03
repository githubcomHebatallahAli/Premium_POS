<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryWithProductsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'productsCount' => $this->products_count,
            'products' => $this->products->map(function ($product) {
                return [
                    'id'=> $product-> id,
                    'image' => $product->image,
                    'name' => $product->name,
                    'priceBeforeDiscount'=>$product->priceBeforeDiscount,
                    'discount' => $product->discount ? number_format($product->discount, 2) . '%' : null,
                    'sellingPrice' => $product->sellingPrice,
                ];
            }),
        ];
    }
}
