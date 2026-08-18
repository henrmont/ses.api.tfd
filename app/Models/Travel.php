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
        'status',
        'total_tariffs',
        'total_taxes',
        'total'
    ];

    protected function status(): Attribute
    {
        if (
            is_null($this->type) ||
            is_null($this->origin) ||
            is_null($this->destination) ||
            (is_null($this->departure_date) && is_null($this->return_date)) ||
            is_null($this->description) ||
            $this->passengers()->doesntExist() ||
            $this->travelRoutes()->doesntExist()
        ) 
            return Attribute::make(get: fn () => false);
        return Attribute::make(get: fn () => true);
    }

    protected function totalTariffs(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->passengers()->sum('tariff') || 0
        );
    }

    protected function totalTaxes(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->passengers()->sum('tax') || 0
        );
    }

    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->totalTariffs + $this->totalTaxes
        );
    }


}
