<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Escort extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'cns',
        'file_cns_id',
        'document',
        'file_document_id',
        'name',
        'relation',
        'birth_date',
        'gender',
        'is_same_address',
        'cep',
        'address',
        'file_address_id',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
    ];

    protected $appends = [
        'status',
    ];

    // Relationships
    public function patientCare(): BelongsToMany
    {
        return $this->belongsToMany(PatientCare::class, 'patient_care_escorts', 'escort_id', 'patient_care_id');
    }

    public function patientCareEscort(): HasOne
    {
        return $this->hasOne(PatientCareEscort::class);
    }

    public function fileDocument(): HasOne
    {
        return $this->hasOne(Archive::class, 'id','file_document_id');
    }

    // Accessors & Mutators
    protected function status(): Attribute
    {
        if (
            is_null($this->relation) ||
            is_null($this->city) ||
            is_null($this->state) ||
            is_null($this->file_cns_id) ||
            is_null($this->file_document_id) ||
            is_null($this->file_address_id)
        ) 
            return Attribute::make(get: fn () => false);
        return Attribute::make(get: fn () => true);
    }
}
