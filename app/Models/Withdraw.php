<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Withdraw extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'personName',
        'creationDate',
        'withdrawnAmount',
        'remainingAmount',
        'description',
    ];


}
