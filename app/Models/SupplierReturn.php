<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierReturn extends Model
{
    protected $fillable = [
        'damage_product_id',
        'returned_quantity',
        'refund_amount',
        'creationDate',
        'note',
    ];

    public function damageProduct()
    {
        return $this->belongsTo(DamageProduct::class);
    }
}
