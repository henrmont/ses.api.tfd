<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'locator',
        'company',
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

    // Accessors & Mutators
    protected $appends = [
        'total_tariffs',
        'total_taxes',
        'total'
    ];

    protected function totalTariffs(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->passengers()->sum('tariff')
        );
    }

    protected function totalTaxes(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->passengers()->sum('tax')
        );
    }

    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->totalTariffs + $this->totalTaxes
        );
    }


}
