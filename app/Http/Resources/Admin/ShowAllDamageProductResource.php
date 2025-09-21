<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllDamageProductResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "product" => $this->whenLoaded("product", function () {
                return [
                    "id" => $this->product->id,
                    "name" => $this->product->name,
                    "mainImage" => $this->product->mainImage,
                     "category" => $this->product->whenLoaded("category", function () {
                        return [
                            "id" => $this->product->category->id,
                            "name" => $this->product->category->name,
                        ];
                    }),
                    "brand" => $this->product->whenLoaded("brand", function () {
                        return $this->product->brand ? [
                            "id" => $this->product->brand->id,
                            "name" => $this->product->brand->name,
                        ] : null;
                    }),
                ];
            }),
           
            "quantity" => $this->quantity,
            "reason" => $this->reason,
            "status" => $this->status,
            "creationDate" => $this->creationDate,
        ];
    }
}
