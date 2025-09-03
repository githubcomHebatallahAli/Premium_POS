<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null,  // تأكد من وجود الـ user قبل الوصول إليه
            'admin' => $this->admin ? [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
            ] : null,
            'categories' => $this->products->groupBy('category_id')->map(function ($products, $categoryId) {
                return [
                    'category_id' => $categoryId,
                    'products' => $products->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'image' => $product->image,
                            'quantity' => $product->pivot->quantity,
                        ];
                    })->values(),
                ];
            })->values(),
        ];

    }

}
