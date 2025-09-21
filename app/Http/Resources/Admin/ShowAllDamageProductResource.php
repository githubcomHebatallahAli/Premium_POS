<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllDamageProductResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            'product' => new ProductResource($this->whenLoaded('product')),
            "quantity" => $this -> quantity,
            "reason" => $this -> reason,
            "status" => $this -> status,
            "creationDate" => $this -> creationDate,
        ];
    }
}
