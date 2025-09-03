<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this -> name,
            'phoNum' => $this -> phoNum,
            'address' => $this -> address,
            'details' => $this -> details,
            'status' => $this -> status,
            'totalPrice'=> $this-> totalPrice,
            'shippingCost' => $this->shippingCost,
            'discount' => $this->discount,
            'finalPrice' => $this -> finalPrice,
            'creationDate' => $this -> creationDate,
            'cart' => new CartInvoiceResource($this),


        ];
    }
}
