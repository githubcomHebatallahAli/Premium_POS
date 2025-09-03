<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'name' => $this -> name,
            'productsCount' => $this->productsCount,
            'image' => $this -> image,
            'status' => $this -> status,
            'products' => $this->products->map(function ($product) {
                return [
                    'image' => $product->image,
                    'name' => $product->name,
                    'priceBeforeDiscount'=>$product->priceBeforeDiscount,
                    'discount' => $product->discount ? number_format($product->discount, 2) . '%' : null,
                    'sellingPrice' => $product->sellingPrice,
                    'quantity'=>$product->quantity,
                    "purchesPrice" => $product -> purchesPrice,
                    "profit" => $product -> profit,
                ];
            }),
        ];
    }
}
