<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientRequest extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'report_id',
        'medical_professional_id',
        'social_professional_id',
        'travel_professional_id',
        'owner_professional_id',
        'cost_assistance_professional_id',
        'accountability_professional_id',
        'hospital_unity_id',
        'type',
        'consultation_date',
        'observation',
        'back_to_owner',
        'back_to_medical',
        'back_to_social',
        'back_to_travel',
        'back_to_cost_assistance',
        'back_from_travel',
        'back_from_cost_assistance',
        'is_owner_bookmark',
        'is_medical_bookmark',
        'is_social_bookmark',
        'is_travel_bookmark',
        'is_cost_assistance_bookmark',
        'is_accountability_bookmark',
        'is_opinion_archived',
        'is_travel_archived',
        'is_cost_assistance_archived',
        'is_accountability_archived'
    ];

    // Scopes
    public function scopeNotPatientBack($query)
    {
        return $query->whereHas('report', function ($q) {
            $q->whereHas('patientCare', function ($subQ) {
                $subQ->whereNull('back_to_user');
            });
        });
    }

    // Relationships
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function hospitalUnity(): BelongsTo
    {
        return $this->belongsTo(HospitalUnity::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PatientRequestAttachment::class);
    }

    public function medicalProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'medical_professional_id');
    }

    public function ownerProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'owner_professional_id');
    }

    public function socialProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'social_professional_id');
    }

    public function travelProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'travel_professional_id');
    }

    public function costAssistanceProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'cost_assistance_professional_id');
    }

    public function accountabilityProfessional(): BelongsTo
    {
        return $this->belongsTo(Professional::class, 'accountability_professional_id');
    }

    public function opinions(): HasMany
    {
        return $this->hasMany(Opinion::class);
    }

    public function travels(): HasMany
    {
        return $this->hasMany(Travel::class);
    }

    public function costAssistances(): HasMany
    {
        return $this->hasMany(CostAssistance::class);
    }

    public function accountabilities(): HasMany
    {
        return $this->hasMany(Accountability::class);
    }

    // Accessors & Mutators
    protected $appends = [
        'owner',
        'owner_status',
        'medical',
        'medical_status',
        'has_medical_opinion',
        'medical_approved_opinion',
        'social',
        'social_status',
        'has_social_opinion',
        'social_approved_opinion',
        'travel',
        'travel_status',
        'has_travel',
        'cost_assistance',
        'cost_assistance_status',
        'has_cost_assistance',
        'has_initial_cost_assistance',
        'has_complementary_cost_assistance',
        'has_cost_assistance_without_dailies',
        'accountability',
        'accountability_status',
    ];

    protected function owner(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->owner_professional_id == Professional::where('user_id', auth()->user()->id)->first()->id ? true : false
        );
    }

    protected function ownerStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => true
        );
    }

    protected function medical(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->medical_professional_id == Professional::where('user_id', auth()->user()->id)->first()->id ? true : false
        );
    }

    protected function medicalStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->opinions()->where('is_approved',true)->where('professional_id',$this->medical_professional_id)->exists()
        );
    }

    protected function hasMedicalOpinion(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->opinions()->whereHas('professional', function ($q) {
                $q->where('type','Médico');
            })->exists()
        );
    }

    protected function medicalApprovedOpinion(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->opinions()->where('is_approved',true)->whereHas('professional', function ($q) {
                $q->where('type','Médico');
            })->first()
        );
    }

    protected function social(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->social_professional_id == Professional::where('user_id', auth()->user()->id)->first()->id ? true : false
        );
    }
    
    protected function socialStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->opinions()->where('is_approved',true)->where('professional_id',$this->social_professional_id)->exists()
        );
    }

    protected function hasSocialOpinion(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->opinions()->whereHas('professional', function ($q) {
                $q->where('type','Assistente Social');
            })->exists()
        );
    }

    protected function socialApprovedOpinion(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->opinions()->where('is_approved',true)->whereHas('professional', function ($q) {
                $q->where('type','Assistente Social');
            })->first()
        );
    }

    protected function travel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->travel_professional_id == Professional::where('user_id', auth()->user()->id)->first()->id ? true : false
        );
    }

    protected function travelStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->travels->contains('status', true)
        );
    }

    protected function hasTravel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->travels()->exists()
        );
    }

    protected function costAssistance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cost_assistance_professional_id == Professional::where('user_id', auth()->user()->id)->first()->id ? true : false
        );
    }

    protected function costAssistanceStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->costAssistances()->exists() && ($this->costAssistances->contains('status', false) ? false : true)
        );
    }

    protected function hasCostAssistance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->costAssistances()->exists()
        );
    }

    protected function hasInitialCostAssistance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->costAssistances()->where('type', 'Inicial')->exists()
        );
    }

    protected function hasComplementaryCostAssistance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->costAssistances()->where('type', 'Complemento')->exists()
        );
    }

    protected function hasCostAssistanceWithoutDailies(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->costAssistances()->whereDoesntHave('costAssistanceDailies')->exists()
        );
    }

    protected function accountability(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->accountability_professional_id == Professional::where('user_id', auth()->user()->id)->first()->id ? true : false
        );
    }

    protected function accountabilityStatus(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->accountabilities()->exists() && ($this->accountabilities->contains('status', false) ? false : true)
        );
    }

}
