<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantImage extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'image_id',
        'product_variant_id',
    ];
}
