<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable  implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;
    const storageFolder= 'Admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'address',
        'phoNum',
        'status',
        'role_id',
        'last_login_at',
        'last_logout_at',
        'session_duration',
        'image'

    ];

    // public function carts()
    // {
    //     return $this->hasMany(Cart::class);
    // }


    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
