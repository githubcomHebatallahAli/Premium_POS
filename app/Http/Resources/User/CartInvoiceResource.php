<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartInvoiceResource extends JsonResource
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
            'totalPrice' => $this-> totalPrice,
            'discount'=> $this->discount,
            'shippingCost' => $this-> shippingCost,
            'finalPrice' => $this-> finalPrice,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null,  
            'admin' => $this->admin ? [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
            ] : null,
            'categories' => $this->order->products->groupBy('category_id')->map(function ($products, $categoryId) {
                return [
                    'category_id' => $categoryId,
                    'products' => $products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'image' => $product->image,
                            'sellingPrice' => $product -> sellingPrice,
                            'quantity' => $product->pivot->quantity,
                            'total' => $product->pivot->total,
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }
}
