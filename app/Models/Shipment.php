<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'supplier_id',
        // 'supplierName',
        'importer',
        'admin_id',
        'creationDate',
        'shipmentProductsCount',
        'totalPrice',
        'status',
        'paidAmount',
        'remainingAmount',
        'discount',
        'extraAmount',
        'discountType',
        'taxType',
        'invoiceAfterDiscount',
        'payment',
        'returnReason',
        'description',
    ];


    // protected $dates = ['creationDate'];

    // public function getFormattedCreationDateAttribute()
    // {
    //     return Carbon::parse($this->creationDate)
    //         ->timezone('Africa/Cairo')
    //         ->format('Y-m-d h:i:s');
    // }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'shipment_products')
        ->withPivot('quantity', 'price','unitPrice', 'returnReason','endDate','product_variant_id','remainingQuantity');
    }

    public function shipmentProducts()
{
    return $this->hasMany(ShipmentProduct::class, 'shipment_id');
}

     public function damages()
     {
         return $this->hasMany(DamageProduct::class);
     }


    protected static function booted()
    {
        static::created(function ($shipment) {
            $shipment->updateShipmentProductsCount();
        });

        static::deleted(function ($shipment) {
            if (method_exists($shipment, 'isForceDeleting') && $shipment->isForceDeleting()) {
                return;
            }

            if (!$shipment->trashed()) {
                $shipment->updateShipmentProductsCount();
            }
        });

static::saving(function ($shipment) {
    $shipment->load('products');
    $shipment->totalPrice = $shipment->calculateTotalPrice();
});

    }

    public function updateShipmentProductsCount()
    {
        $this->shipmentProductsCount = $this->products()->whereNull('deleted_at')->count();
        $this->saveQuietly();
    }

    public function getShipmentProductsCountAttribute()
    {
        return $this->attributes['shipmentProductsCount'] ?? 0;
    }

// public function calculateTotalPrice()
// {
//     $this->load('products');
//     return $this->products->sum(function ($product) {
//         return $product->pivot->quantity * $product->pivot->price;
//     });
// }

public function calculateTotalPrice()
{
    $this->load('products');
    return $this->products->sum(function ($product) {
        return $product->pivot->price;
    });
}

public function supplier()
{
    return $this->belongsTo(Supplier::class);
}

public function admin()
{
    return $this->belongsTo(Admin::class);
}

}
