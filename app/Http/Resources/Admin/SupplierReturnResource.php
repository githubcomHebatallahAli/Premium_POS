<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierReturnResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'damage_product' => new DamageProductResource($this->whenLoaded('damageProduct')),
            'returnedQuantity' => $this->returned_quantity,
            'refundAmount' => number_format($this->refund_amount, 2, '.', ''),
            'note' => $this->note,
            'creationDate' => $this->creationDate,
        ];
    }
}
