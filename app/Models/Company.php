<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
     use HasFactory;
    const storageFolder= 'Company';
    protected $fillable = [
        'name',
        'address',
        'logo',
        'firstPhone',
        'secondPhone',
        'commercialNo',
        'taxNo',
        'admin_id',
        'creationDate',
    ];
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
