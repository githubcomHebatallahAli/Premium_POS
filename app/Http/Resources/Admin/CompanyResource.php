<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
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
            'name' => $this->name,
            'address' => $this->address,
            'logo' => $this->logo,
            'firstPhone' => $this->firstPhone,
            'secondPhone' => $this->secondPhone,
            'commercialNo' => $this->commercialNo,
            'taxNo' => $this->taxNo,
            'admin' => $this->admin ? [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
            ] : null,
            'creationDate' => $this->creationDate,
        ];
    }
}
