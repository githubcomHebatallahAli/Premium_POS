<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
        use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'name',
        'productsCount',
        'creationDate',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    protected static function booted()
    {
        static::created(function ($brand) {
            $brand->productsCount = $brand->products()->count();
            $brand->save();
        });



        static::deleted(function ($brand) {
            if (method_exists($brand, 'isForceDeleting') && $brand->isForceDeleting()) {
                return;
            }

            if (!$brand->trashed()) {
                $brand->productsCount = $brand->products()->count();
                $brand->save();
            }
        });

    }



        public function getProductsCountAttribute()
        {
            return $this->products()->count();
        }
}
