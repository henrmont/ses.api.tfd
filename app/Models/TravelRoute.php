<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelRoute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'travel_id',
        'origin',
        'destination',
        'distance'
    ];
}
