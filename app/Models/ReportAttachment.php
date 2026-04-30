<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportAttachment extends Model
{ 
    use SoftDeletes;
    
    protected $fillable = [
        'report_id',
        'archive_id',
        'name',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function archive(): BelongsTo
    {
        return $this->belongsTo(Archive::class);
    }
}
