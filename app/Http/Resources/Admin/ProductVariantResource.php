<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
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
            'product' => new ProductResource($this->product),
            "color" => $this -> color,
            "size" => $this -> size,
            "clothes" => $this -> clothes,
            "sku" => $this -> sku,
            "barcode" => $this -> barcode,
            "sellingPrice" => $this -> sellingPrice,
            'images' => $this -> images,
            'creationDate' => $this -> creationDate,
            'notes' => $this -> notes,
        ];
    }
}
