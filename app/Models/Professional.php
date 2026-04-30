<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Professional extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'cns',
        'registration',
        'professional_register',
        'cbo',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patientMedicalRequests(): HasMany
    {
        return $this->hasMany(PatientRequest::class, 'medical_professional_id');
    }

    public function patientSocialRequests()
    {
        return $this->hasMany(PatientRequest::class, 'social_professional_id');
    }

    public function patientTravelRequests()
    {
        return $this->hasMany(PatientRequest::class, 'travel_professional_id');
    }

    public function patientCostAssistanceRequests()
    {
        return $this->hasMany(PatientRequest::class, 'cost_assistance_professional_id');
    }

    public function patientPaymentRequests()
    {
        return $this->hasMany(PatientRequest::class, 'payment_professional_id');
    }
}
