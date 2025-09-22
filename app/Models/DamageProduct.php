<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DamageProduct extends Model
{
     use HasFactory, SoftDeletes;
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'shipment_product_id',
        'quantity',
        'reason',
        'status',
        'creationDate',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    
    public function shipmentProduct()
    {
        return $this->belongsTo(ShipmentProduct::class);
    }
}
