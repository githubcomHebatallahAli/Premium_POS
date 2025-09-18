<?php

namespace App\Http\Resources\Admin;

use App\Models\ShipmentProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalQuantity = ShipmentProduct::where('product_id', $this->id)->sum('remainingQuantity');
        // $totalQuantity = $this->shipments()->sum('shipment_products.quantity');
        
        return [
            "id" => $this -> id,
            // "barcode" => $this -> barcode,
            "mainImage" => $this -> mainImage,
            "name" => $this -> name ,
            'categoryName' => $this->category->name ?? null,
            'brandName' => $this->brand->name ?? null,
            "sellingPrice" => $this -> sellingPrice,
            'totalQuantity' => $totalQuantity,
            "creationDate" => $this -> creationDate,
        ];
    }
}
