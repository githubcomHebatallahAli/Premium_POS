<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purpose extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'transactionReason',
        'creationDate',
    ];

        public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
