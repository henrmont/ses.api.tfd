<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientCareEscort extends Model
{
    protected $fillable = [
        'patient_care_id',
        'escort_id',
    ];

    // Relationships
    public function patientCare(): BelongsTo
    {
        return $this->belongsTo(PatientCare::class);
    }

    public function escort(): BelongsTo
    {
        return $this->belongsTo(Escort::class);
    }
}
