<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalQuantity = $this->shipments()->sum('quantity');
        $firstImage = $this->images->first();
        return [
            "id" => $this -> id,
            "barcode" => $this -> barcode,
            "image" => $firstImage ? $firstImage->image : null, 
            "name" => $this -> name ,
            'categoryName' => $this->category->name ?? null,
            'brandName' => $this->brand->name ?? null,
            "sellingPrice" => $this -> sellingPrice,
            'totalQuantity' => $totalQuantity,
            "creationDate" => $this -> creationDate,
           
        ];
    }
}
