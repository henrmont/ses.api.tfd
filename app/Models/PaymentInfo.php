<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentInfo extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'patient_request_id',
        'description',
    ];

    // Relationships
    public function patientRequest(): BelongsTo
    {
        return $this->belongsTo(PatientRequest::class);
    }
}
