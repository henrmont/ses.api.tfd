<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentAttachment extends Model
{
    protected $fillable = [
        'payment_id',
        'patient_request_attachment_id'
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function patientRequestAttachment()
    {
        return $this->belongsTo(PatientRequestAttachment::class);
    }
}
