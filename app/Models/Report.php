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
        'specialty',
        'lawsuit',
        'diagnosis',
        'is_editable',
        'is_export'
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

    // Accessors & Mutators
    protected $appends = [
        'has_patient_request',
        'has_entrance_or_lawsuit',
        'has_entrance_or_lawsuit_finished'
    ];

    protected function hasPatientRequest(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->patientRequests()->exists()
        );
    }

    protected function hasEntranceOrLawsuit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->patientRequests()->whereIn('type', ['Entrada', 'Ação Judicial'])->exists()
        );
    }

    protected function hasEntranceOrLawsuitFinished(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->patientRequests
                ->whereIn('type', ['Entrada', 'Ação Judicial'])
                ->whereNotNull('medical_approved_opinion')
                ->whereNotNull('social_approved_opinion')
                ->isNotEmpty()
        );
    }

    
   
}
