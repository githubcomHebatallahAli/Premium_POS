<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
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
        static::created(function ($category) {
            $category->productsCount = $category->products()->count();
            $category->save();
        });



        static::deleted(function ($category) {
            if (method_exists($category, 'isForceDeleting') && $category->isForceDeleting()) {
                return;
            }

            if (!$category->trashed()) {
                $category->productsCount = $category->products()->count();
                $category->save();
            }
        });

    }



        public function getProductsCountAttribute()
        {
            return $this->products()->count();
        }

}
