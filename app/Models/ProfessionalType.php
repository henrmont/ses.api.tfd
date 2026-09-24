<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfessionalType extends Model
{
    protected $fillable = [
        'professional_id',
        'type',
    ];

    // Relationships
    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }
}
