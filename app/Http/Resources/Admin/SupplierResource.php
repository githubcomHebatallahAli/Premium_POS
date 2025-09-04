<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
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
            'supplierName' => $this -> supplierName,
            "phoNum" => $this -> phoNum,
            "place" => $this -> place,
            "shipmentsCount" => $this -> shipmentsCount,
            "companyName" => $this -> companyName,
            "description" => $this -> description,
            "creationDate" => $this -> creationDate,
        ];
    }
}
