<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'invoice_id',
        'shipment_product_id',
        'product_variant_id',
        'quantity',
        'total',
        'profit',
        'returnReason'
    ];

        public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function shipmentProduct()
    {
        return $this->belongsTo(ShipmentProduct::class, 'shipment_product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    
}
