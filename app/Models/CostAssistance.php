<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class CostAssistance extends Model
{
    protected $fillable = [
        'patient_request_id',
        'name',
        'type',
        'passenger_id',
        'bank',
        'agency',
        'account'
    ];

    public function patientRequest()
    {
        return $this->belongsTo(PatientRequest::class);
    }

    public function costAssistanceDailies()
    {
        return $this->hasMany(CostAssistanceDaily::class);
    }

    public function passenger()
    {
        return $this->belongsTo(Passenger::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    protected $appends = [
        'status',
        'total_amount',
        'total_dailies',
    ];

    // Accessors & Mutators
    protected function status(): Attribute
    {
        if (
            is_null($this->passenger_id) ||
            is_null($this->bank) ||
            is_null($this->agency) ||
            is_null($this->account) ||
            $this->costAssistanceDailies()->doesntExist() 
        ) 
            return Attribute::make(get: fn () => false);
        return Attribute::make(get: fn () => true);
    }


    protected function totalAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->costAssistanceDailies()->sum('amount')
        );
    }


    protected function totalDailies(): Attribute
    {
        $total = $this->costAssistanceDailies()->with('dailyCost')->get()->reduce(function ($carry, $item) {
            return $carry + ($item->amount * $item->dailyCost->value);
        }, 0);
        return Attribute::make(
            get: fn () => $total
        );
    }
    
}
