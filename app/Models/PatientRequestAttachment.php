<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientRequestAttachment extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'patient_request_id',
        'archive_id',
        'name',
    ];

    public function patientRequest()
    {
        return $this->belongsTo(PatientRequest::class);
    }

    public function archive()
    {
        return $this->belongsTo(Archive::class);
    }
}
