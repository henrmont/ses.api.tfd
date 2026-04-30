<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientCare extends Model
{
    protected $fillable = [
        'patient_id',
        'module_id',
        'is_valid',
        'user_id',
        'is_archived',
    ];

    // Scopes
    public function scopeTfd($query)
    {
        return $query->whereHas('module', function ($q) {
            $q->where('name', 'tfd');
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function escorts(): BelongsToMany
    {
        return $this->belongsToMany(Escort::class, 'patient_care_escorts', 'patient_care_id', 'escort_id')->withPivot('id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function patientRequests(): HasMany
    {
        return $this->hasMany(PatientRequest::class);
    }

    protected $appends = [
        'status',
        'owner'
    ];

    // Accessors & Mutators
    protected function status(): Attribute
    {
        if (
            is_null($this->patient->marital_status) ||
            (is_null($this->patient->phone) && is_null($this->patient->cell_phone)) ||
            is_null($this->patient->email) ||
            is_null($this->patient->mother_name) ||
            is_null($this->patient->city) ||
            is_null($this->patient->state) ||
            is_null($this->patient->patientInfo->control_number) ||
            $this->reports()->doesntExist() ||
            is_null($this->patient->file_cns_id) ||
            is_null($this->patient->file_document_id) ||
            is_null($this->patient->file_address_id) ||
            is_null($this->patient->patientInfo->file_protocol_id)
        ) 
            return Attribute::make(get: fn () => false);
        return Attribute::make(get: fn () => true);
    }

    protected function owner(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user_id == auth()->user()->id ? true : false
        );
    }
    
}
