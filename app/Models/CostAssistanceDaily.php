<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostAssistanceDaily extends Model
{
    protected $fillable = [
        'cost_assistance_id',
        'daily_cost_id',
        'amount',
    ];

    public function costAssistance()
    {
        return $this->belongsTo(CostAssistance::class);
    }

    public function dailyCost()
    {
        return $this->belongsTo(DailyCost::class);
    }
}
