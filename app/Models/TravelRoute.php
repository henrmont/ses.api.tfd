<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelRoute extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'travel_id',
        'origin',
        'destination',
        'flight',
        'airplane',
        'departure',
        'arrival',
        'class',
        'scales',
        'family',
        'distance'
    ];

    public function travel(): BelongsTo
    {
        return $this->belongsTo(Travel::class);
    }
}
