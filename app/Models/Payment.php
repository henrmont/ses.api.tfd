<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'patient_request_id',
        'cost_assistance_id',
        'travel_id',
        'payment_professional_id',
        'is_payment_bookmark',
        'is_payment_archived',
        'sigadoc',
        'creditor',
        'document_number'
    ];

    // Relationships
    public function patientRequest(): BelongsTo
    {
        return $this->belongsTo(PatientRequest::class);
    }

    public function costAssistance()
    {
        return $this->belongsTo(CostAssistance::class);
    }

    public function travel()
    {
        return $this->belongsTo(Travel::class);
    }

    public function paymentProfessional()
    {
        return $this->belongsTo(Professional::class, 'payment_professional_id');
    }

    public function paymentAttachments()
    {
        return $this->hasMany(PaymentAttachment::class);
    }

    // Accessors & Mutators
    protected $appends = [
        'payment',
        'payment_status',
    ];

    protected function payment(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payment_professional_id == Professional::where('user_id', auth()->user()->id)->first()->id ? true : false
        );
    }

    protected function paymentStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => true
        );
    }
}
