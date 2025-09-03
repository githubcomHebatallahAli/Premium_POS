<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dept extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'customerName',
        'sellerName',
        'paidAmount',
        'remainingAmount',
        'creationDate',
        'deptProductCount',
        'totalDeptPrice',
        'discount',
        'deptAfterDiscount',
        'profit',
        'extraAmount'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'dept_products')
                    ->withPivot('quantity', 'total','profit');
    }

    protected static function booted()
{

    static::created(function ($dept) {
        $dept->load('products');
        $dept->updateDeptProductCount();
    });

    static::deleted(function ($dept) {
        if (method_exists($dept, 'isForceDeleting') && $dept->isForceDeleting()) {
            return;
        }

        if (!$dept->trashed()) {
            $dept->updateDeptProductCount();
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

public function updateDeptProductCount()
{
    $this->deptProductCount = $this->products()
    ->whereNull('deleted_at')
    ->count();
    $this->saveQuietly();
}

public function getDeptProductCountAttribute()
{
    return $this->attributes['deptProductCount'] ?? 0;
}
}
