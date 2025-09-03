<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes;
    const storageFolder= 'Products';
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'sellingPrice',
        'discount',
        'image',
        'creationDate',
        'color',
        'size',
        'clothes',
        'country',
        'endDate',
        'code',
        'description',
    ];

    protected static function boote()
    {
        static::created(function ($products) {
            $products->category->increment('productsCount');
        });

        static::deleted(function ($products) {
            $products->category->decrement('productsCount');
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    
    public function shipments()
    {
        return $this->belongsToMany(Shipment::class, 'shipment_products')
        ->withPivot('quantity', 'price','unitPrice');
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class ,'invoice_products' )
        ->withPivot('quantity','total','profit','shipment_id');
    }

    public function depts()
    {
        return $this->belongsToMany(Dept::class ,'dept_products' )
        ->withPivot('quantity','total','profit');
    }

    public function agentInvoices()
    {
        return $this->belongsToMany(Agentinvoice::class ,'agentinvoice_products' )
        ->withPivot('quantity','total');
    }

    // public function newProducts()
    // {
    //     return $this->hasMany(Newproduct::class);
    // }

    // public function premProducts()
    // {
    //     return $this->hasMany(Premproduct::class);
    // }

    // public function orders()
    // {
    //     return $this->belongsToMany(Order::class, 'order_products')
    //                 ->withPivot('quantity', 'total','profit')
    //                 ->withTimestamps();
    // }

    // public function carts()
    // {
    //     return $this->belongsToMany(Cart::class, 'cart_products')
    //                 ->withPivot('quantity','total','profit')
    //                 ->withTimestamps();
    // }


    
public function getStockAttribute()
{
    return $this->shipments()->sum('shipment_products.quantity');
}

protected $appends = ['stock'];

}
