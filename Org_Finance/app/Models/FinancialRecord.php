<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialRecord extends Model
{
    protected $fillable = ['ref', 'title', 'type', 'category', 'amount', 'date', 'status'];

    protected $casts = [
        'amount' => 'float',
        'date'   => 'date:Y-m-d',
    ];
}