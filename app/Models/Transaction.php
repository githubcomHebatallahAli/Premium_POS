<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'admin_id',
        'type',
        'purpose_id',
        'creationDate',
        'amount',
        'remainingAmount',
        'description',
    ];

    
      public function purpose()
    {
        return $this->belongsTo(Purpose::class);
    }
        public function admin()
        {
            return $this->belongsTo(Admin::class);
        }
}
