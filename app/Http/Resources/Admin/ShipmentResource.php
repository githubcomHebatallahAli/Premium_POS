<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentResource extends JsonResource
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
            // 'supplierName' => $this -> supplierName,
            'supplierName' => $this->supplier->supplierName,
            'totalPrice' => $this -> totalPrice,
            'paidAmount'=> $this ->paidAmount,
            'remainingAmount' => $this -> remainingAmount,
            'status' => $this -> status,
            'creationDate' => $this -> creationDate,

        ];
    }
}
