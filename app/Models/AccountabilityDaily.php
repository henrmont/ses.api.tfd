<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountabilityDaily extends Model
{
    protected $fillable = [
        'accountability_id',
        'daily_cost_id',
        'amount',
    ];

    public function accountability()
    {
        return $this->belongsTo(Accountability::class);
    }

    public function dailyCost()
    {
        return $this->belongsTo(DailyCost::class);
    }
}
