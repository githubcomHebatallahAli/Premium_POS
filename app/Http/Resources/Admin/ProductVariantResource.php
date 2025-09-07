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
          'id' => $this->id,
            'color' => $this->color,
            'size' => $this->size,
            'clothes' => $this->clothes,
            'sellingPrice' => $this->sellingPrice,
            'images' => $this->images,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'notes' => $this->notes,
            'creationDate' => $this->creationDate
        ];
    }
}
