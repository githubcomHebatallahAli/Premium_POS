<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;

use Illuminate\Http\Resources\Json\JsonResource;


class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        $response = [
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
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(fn($img) => [
                    'id' => $img->id,
                    'name' => $img->name,
                    "image" => $this->images->map(fn($img) => url($img->path))->toArray(),
                    
                ]);
            }),
        ];

        if ($this->hasRealVariants()) {
            $response['variants'] = ProductVariantResource::collection($this->whenLoaded('variants'));
        }

        return $response;
    }
    
    private function hasRealVariants()
    {
        if (!$this->relationLoaded('variants')) {
            return false;
        }
        
    
        if ($this->variants->count() > 1) {
            return true;
        }
        
        if ($this->variants->count() === 1) {
            $variant = $this->variants->first();
          
            return $variant->color !== null || 
                   $variant->size !== null || 
                   $variant->clothes !== null;
        }
        
        return false;
    }
}
