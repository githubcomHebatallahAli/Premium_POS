<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\Auth\AdminRegisterResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'admin' => new AdminRegisterResource($this->admin),
            'purpose' => new PurposeResource($this->purpose),
            'type'=> $this ->type,
            'amount'=> $this ->amount,
            'remainingAmount'=> $this ->remainingAmount,
            'description'=> $this ->description,
            'creationDate' => $this -> creationDate,
        ];
    }
}
