<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCost extends Model
{
    protected $fillable = [
        'name',
        'value',
        'sigtap_code',
        'overnight',
    ];
}
