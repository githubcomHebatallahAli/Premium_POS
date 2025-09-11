<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
  
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'color' => $this->color,
            'size' => $this->size,
            'clothes' => $this->clothes,
            'sellingPrice' => $this->sellingPrice,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'notes' => $this->notes,
            'creationDate' => $this->creationDate,
         'image' => $this->whenLoaded('images', function () {
    return $this->images->map(fn($img) => [
        'id' => $img->id,
        'name' => $img->name,
        'url' => url($img->image), // تعديل هنا
    ]);
}),

        ];
    }
}
