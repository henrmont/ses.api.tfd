<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Patient extends Model
{
    protected $fillable = [
        'name',
        'cns',
        'file_cns_id',
        'document_type',
        'document',
        'file_document_id',
        'sigadoc',
        'birth_date',
        'gender',
        'newborn',
        'race',
        'ethnicity',
        'marital_status',
        'mother_name',
        'father_name',
        'naturalness',
        'phone',
        'cell_phone',
        'email',
        'profession',
        'deficiency',
        'file_deficiency_id',
        'cep',
        'address',
        'file_address_id',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
    ];

    // Relationships
    public function patientInfo(): HasOne
    {
        return $this->hasOne(PatientInfo::class);
    }

    public function patientCares(): HasMany
    {
        return $this->hasMany(PatientCare::class);
    }

    public function fileDocument(): belongsTo
    {
        return $this->belongsTo(Archive::class, 'file_document_id');
    }
    
}
