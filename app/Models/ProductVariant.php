<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
      use HasFactory, SoftDeletes;
    const storageFolder= 'ProductVariants';
    protected $fillable = [
        'product_id',
        'color',
        'size',
        'clothes',
        'sku',
        'barcode',
        'sellingPrice',
        'creationDate',
        'notes',
    ];

        public function images()
    {
        return $this->belongsToMany(Image::class, 'variant_images', 'product_variant_id', 'image_id');
                    
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    

    protected $casts = [
        'images' => 'array',
    ];
}
