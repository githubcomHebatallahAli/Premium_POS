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
            // 'quantity'=>$this->quantity,
            // 'priceBeforeDiscount'=>$this->priceBeforeDiscount,
            // 'discount' => $this->discount ? number_format($this->discount, 2) . '%' : null,
            "sellingPrice" => $this -> sellingPrice,
            // "purchesPrice" => $this -> purchesPrice,
            // "profit" => $this -> profit,
            'image' => $this -> image,
            'category' => new MainResource($this->category),
            'brand' => new MainResource($this->brand),
            'creationDate' => $this -> creationDate,
            'color' => $this -> color,
            'size' => $this -> size,
            'clothes' => $this -> clothes,
            'country' => $this -> country,
            'endDate' => $this -> endDate,
            'code' => $this -> code,
            'description' => $this -> description,
            "totalQuantity" => $totalQuantity,

        ];
    }
}
