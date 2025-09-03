<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use App\Http\Resources\ImageResource;
use App\Http\Resources\Admin\RoleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRegisterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'name'=>$this->name,
            'email'=>$this->email,
            'role' => new RoleResource($this->role),
            'phoNum' => $this -> phoNum ,
            'status' => $this -> status,
            'address' => $this -> address,
            'image' => $this ->image,
            'salary' => $this -> salary,
            'creationDate' => $this -> creationDate,
            'ip' => $this -> ip,
            'last_login_at' => $this -> last_login_at,
            'last_logout_at' => $this -> last_logout_at,
            'session_duration' => $this -> session_duration,
        ];
    }
}
