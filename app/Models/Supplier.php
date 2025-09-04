<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'supplierName',
        'phoNum',
        'place',
        'shipmentsCount',
        'companyName',
        'description',
        'creationDate',
    ];

    public function shipment()
    {
        return $this->hasMany(Shipment::class);
    }

}
