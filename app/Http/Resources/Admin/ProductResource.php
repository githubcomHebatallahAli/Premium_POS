<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use App\Http\Resources\ImageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalQuantity = $this->shipments()->sum('quantity');
        return [
            "id" => $this -> id,
            "name" => $this -> name ,
            // 'priceBeforeDiscount'=>$this->priceBeforeDiscount,
            // 'discount' => $this->discount ? number_format($this->discount, 2) . '%' : null,
            "sellingPrice" => $this -> sellingPrice,
            'mainImage' => $this -> mainImage,
            'category' => new MainResource($this->category),
            'brand' => new MainResource($this->brand),
            'creationDate' => $this -> creationDate,
            'country' => $this -> country,
            'barCode' => $this -> barCode,
            'sku' => $this -> sku,
            'description' => $this -> description,
            "totalQuantity" => $totalQuantity,
        ];
    }
}
