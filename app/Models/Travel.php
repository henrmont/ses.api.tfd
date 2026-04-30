<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Travel extends Model
{
    use SoftDeletes;

    protected $table = 'travels';
    
    protected $fillable = [
        'patient_request_id',
        'transportation',
        'type',
        'origin',
        'destination',
        'departure_date',
        'return_date',
        'description',
        'os',
        'locator'
    ];

    public function patientRequest(): BelongsTo
    {
        return $this->belongsTo(PatientRequest::class);
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }

    public function travelRoutes(): HasMany
    {
        return $this->hasMany(TravelRoute::class);
    }


}
