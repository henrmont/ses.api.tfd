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
    ];

    public function patientRequest()
    {
        return $this->belongsTo(PatientRequest::class);
    }

    public function costAssistanceDailies()
    {
        return $this->hasMany(CostAssistanceDaily::class);
    }

    // public function payment()
    // {
    //     return $this->hasOne(Payment::class);
    // }

    protected $appends = [
        'total_dailies',
    ];

    // Accessors & Mutators
    protected function totalDailies(): Attribute
    {
        $total = $this->costAssistanceDailies()->with('dailyCost')->get()->reduce(function ($carry, $item) {
            return $carry + ($item->amount * $item->dailyCost->value);
        }, 0);
        return Attribute::make(
            get: fn () => $total
        );
    }

    // protected function hasPaid(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn () => $this->payment()->where('has_paid',true)->exists()
    //     );
    // }
}
