<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeptProduct extends Model
{
    protected $fillable = [
        'product_id',
        'dept_id',
        'quantity',
        'total',
        'profit'
    ];
}
