<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalUnity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'cnes',
    ];
}
