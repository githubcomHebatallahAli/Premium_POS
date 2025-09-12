<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

use Illuminate\Http\Resources\Json\JsonResource;


class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sellingPrice' => $this->sellingPrice,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name
                ];
            }),
            'brand' => $this->whenLoaded('brand', function () {
                return $this->brand ? [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name
                ] : null;
            }),
            'description' => $this->description,
            'country' => $this->country,
            'barcode' => $this->barcode,
            'sku' => $this->sku,
            'creationDate' => $this->creationDate,
            'mainImage' => $this->mainImage,
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')), 
        ];
}
}