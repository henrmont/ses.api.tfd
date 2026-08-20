<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accountability extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'patient_request_id',
        'name',
    ];

    public function patientRequest()
    {
        return $this->belongsTo(PatientRequest::class);
    }

    public function accountabilityDailies()
    {
        return $this->hasMany(AccountabilityDaily::class);
    }

    protected $appends = [
        'status',
        'total_amount',
        'total_dailies',
    ];

    // Accessors & Mutators
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->accountabilityDailies()->exists()
        );
    }

    protected function totalAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->accountabilityDailies()->sum('amount')
        );
    }

    protected function totalDailies(): Attribute
    {
        $total = $this->accountabilityDailies()->with('dailyCost')->get()->reduce(function ($carry, $item) {
            return $carry + ($item->amount * $item->dailyCost->value);
        }, 0);
        return Attribute::make(
            get: fn () => $total
        );
    }
}
