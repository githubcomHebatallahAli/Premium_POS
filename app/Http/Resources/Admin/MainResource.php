<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MainResource extends JsonResource
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
            'name' => $this -> name,
            'productsCount' => $this->productsCount,
            // 'image' => $this -> image,
            // 'status' => $this -> status,
            'creationDate' => $this -> creationDate,
            // 'role' => new RoleResource($this->role),
        ];
    }
}
