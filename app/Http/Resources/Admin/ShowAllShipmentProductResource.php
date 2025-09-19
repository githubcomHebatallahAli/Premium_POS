<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllShipmentProductResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            "id" => $this -> id,
            "productName" => $this -> product->name ,
            "variant" => $this -> variant ? [
                'id' => $this->variant->id,
                'color' => $this->variant->color,
                'size' => $this->variant->size,
                'clothes' => $this->variant->clothes,
            ] : null,
            // 'supplierName' => $this->shipment->supplier->supplierName ?? null,
            // 'importer' => $this -> shipment->importer ?? null ,
            // 'place' => $this ->shipment->supplier->place ?? null,
            "quantity" => $this -> quantity,
            "remainingQuantity" => $this -> remainingQuantity,
            // "price" => number_format($this->price, 2, '.', ''),
            // "unitPrice" => number_format($this->unitPrice, 2, '.', ''),
            // 'returnReason' => $this -> returnReason,
            'endDate' => $this -> endDate,
            "creationDate" => $this -> creationDate,
        ];
    }
}
