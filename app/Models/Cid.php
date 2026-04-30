<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cid extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'competence_id',
        'code',
        'name',
        'grievance',
        'gender',
        'stadium',
        'irradiated_fields',
    ];

    public function competence(): BelongsTo
    {
        return $this->belongsTo(Competence::class);
    }

}
