<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShipmentProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'shipment_id',
        'price',
        'quantity',
        'unitPrice',
        'returnReason',
        'endDate',
    ];
}
