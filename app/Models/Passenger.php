<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Passenger extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'travel_id',
        'is_patient',
        'patient_id',
        'escort_id',
        'tariff',
        'tax'
    ];

    public function travel(): BelongsTo
    {
        return $this->belongsTo(Travel::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function escort(): BelongsTo
    {
        return $this->belongsTo(Escort::class);
    }
}
