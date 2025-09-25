<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowAllTransactionResource extends JsonResource
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
           'admin' => $this->admin ? [
            'id' => $this->admin->id,
            'name' => $this->admin->name,
            ] : null,
            'purpose' => $this->purpose ? [
                'id' => $this->purpose->id,
                'transactionReason' => $this->purpose->transactionReason,
            ] : null,
            'type'=> $this ->type,
            'amount'=> $this ->amount,
            'description'=> $this ->description,
            'creationDate' => $this -> creationDate,
        ];
    }
}
