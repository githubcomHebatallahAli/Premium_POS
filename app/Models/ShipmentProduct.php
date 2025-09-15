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
        'product_variant_id',
        'remainingQuantity',
    ];


        public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
