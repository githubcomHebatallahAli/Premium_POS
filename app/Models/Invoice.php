<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'customerName',
        // 'sellerName',
        'customerPhone',
        'admin_id',
        'creationDate',
        'invoiceProductCount',
        'totalInvoicePrice',
        'discount',
        'invoiceAfterDiscount',
        'profit',
        'extraAmount',
        'status',
        'payment',
        'pullType',
        'paidAmount',
        'remainingAmount',
        'discountType',
        'taxType',
        'returnReason' 
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'invoice_products')
                    ->withPivot('quantity', 'total','profit','shipment_product_id',
                    'returnReason','product_variant_id');
    }

    public function admin()
{
    return $this->belongsTo(Admin::class);
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

    public function invoiceProducts()
    {
        return $this->hasMany(InvoiceProduct::class);
    }

}
