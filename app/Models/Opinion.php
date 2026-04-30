<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opinion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_request_id',
        'professional_id',
        'name',
        'content',
        'is_approved',
    ];

    // Relationships
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'professional_id');
    }

    public function patientRequest(): BelongsTo
    {
        return $this->belongsTo(PatientRequest::class, 'patient_request_id');
    }

    // Accessors & Mutators
    protected $appends = [
        'my_opinion'
    ];

    protected function myOpinion(): Attribute
    {
        return Attribute::make(get: fn () => $this->professional_id == Professional::where('user_id',auth()->user()->id)->first()->id ? true : false);
    }
    
}
