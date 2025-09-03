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
        'shipment_id',
        'quantity',
        'total',
        'profit'
    ];

    
}
