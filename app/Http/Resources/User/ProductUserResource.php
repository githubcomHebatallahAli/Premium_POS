<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use App\Http\Resources\ImageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductUserResource extends JsonResource
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
            "name" => $this -> name ,
            'priceBeforeDiscount'=>$this->priceBeforeDiscount,
            'discount' => $this->discount ? number_format($this->discount, 2) . '%' : null,
            'sellingPrice' => $this->sellingPrice,
            'image' => $this -> image,
        ];
    }
}
