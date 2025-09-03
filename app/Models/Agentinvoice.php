<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Agentinvoice extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'responsibleName',
        'distributorName',
        'creationDate',
        'invoiceProductCount',
        'status',
        'totalInvoicePrice',

    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'agentinvoice_products')
                    ->withPivot('quantity', 'total');
    }

    protected static function booted()
{

    static::created(function ($invoice) {
        $invoice->load('products');
        $invoice->updateInvoiceProductCount();
    });

    static::deleted(function ($invoice) {
        if (method_exists($invoice, 'isForceDeleting') && $invoice->isForceDeleting()) {
            return;
        }

        if (!$invoice->trashed()) {
            $invoice->updateInvoiceProductCount();
        }
    });

}

public function calculateTotalPrice()
{
    $total = 0;

    foreach ($this->products as $product) {
        $total += $product->pivot->total;
    }

    return $total;
}


public function updateInvoiceProductCount()
{
    $this->invoiceProductCount = $this->products()
    ->whereNull('deleted_at')
    ->count();
    $this->saveQuietly();
}

public function getInvoiceProductCountAttribute()
{
    return $this->attributes['invoiceProductCount'] ?? 0;
}
}
