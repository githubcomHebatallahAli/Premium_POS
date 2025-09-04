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
            'importer' => $this -> importer ,
            'supplierName' => $this->supplier->supplierName,
            'supplier_id' => $this -> supplier_id,
            'invoiceAfterDiscount' => $this -> invoiceAfterDiscount,
            // 'paidAmount'=> $this ->paidAmount,
            // 'remainingAmount' => $this -> remainingAmount,
            'status' => $this -> status,
            'creationDate' => $this -> creationDate,

        ];
    }
}
