<?php

namespace App\Http\Resources\Admin;

use App\Models\ShipmentProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
  
    public function toArray(Request $request): array
    {
          $totalQuantity = ShipmentProduct::where('product_variant_id', $this->id)->sum('quantity');
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'color' => $this->color,
            'size' => $this->size,
            'clothes' => $this->clothes,
            'sellingPrice' => $this->sellingPrice,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'notes' => $this->notes,
            'creationDate' => $this->creationDate,
            'totalQuantity'=> $totalQuantity,   
        ];
    }
}
