<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Report extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'patient_care_id',
        'protocol',
        'cid_id',
        'lawsuit',
        'diagnosis',
    ];

    // Relationships
    public function patientCare(): BelongsTo
    {
        return $this->belongsTo(PatientCare::class);
    }

    public function cid(): BelongsTo
    {
        return $this->belongsTo(Cid::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }

    public function patientRequests(): HasMany
    {
        return $this->hasMany(PatientRequest::class);
    }

    
   
}
