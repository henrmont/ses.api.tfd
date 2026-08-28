<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientInfo extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';
    
    protected $fillable = [
        'patient_id',
        'observation',
        'control_number',
        'file_protocol_id',
    ];

    // Relationships
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
    
}
