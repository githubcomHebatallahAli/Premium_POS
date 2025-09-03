<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Auth\AdminRegisterResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShipmentProductResource extends JsonResource
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
            'supplierName' => $this->supplier->supplierName,
            'importer' => $this -> importer ,
            'admin' => new AdminRegisterResource($this->admin),
            // 'place' => $this -> place,
            'place' => $this ->supplier->place,
            "totalPrice" => number_format($this->totalPrice, 2, '.', ''),
            'discount'            => number_format($this->discount ?? 0, 2, '.', ''),
            'extraAmount'         => number_format($this->extraAmount ?? 0, 2, '.', ''),
            'invoiceAfterDiscount'=> number_format($this->invoiceAfterDiscount ?? 0, 2, '.', ''),
            'discountType'      => $this->discountType,
            'taxType'           => $this->taxType,
            'shipmentProductsCount' => $this -> shipmentProductsCount,
            'creationDate' => $this -> creationDate,
            'status' => $this -> status,
            'payment' => $this -> payment,
            'paidAmount' => number_format($this->paidAmount, 2, '.', ''),
            'remainingAmount' => number_format($this->remainingAmount, 2, '.', ''),
            'products' => $this->products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $product->pivot->quantity,
                    'price' => $product->pivot->price,
                    'unitPrice' => $product->pivot->unitPrice,
                ];
            }),

        ];
    }
}
