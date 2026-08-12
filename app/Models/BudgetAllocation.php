<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAllocation extends Model
{
    protected $fillable = [
        'program',
        'active_project',
        'nature_of_expenditure',
        'source',
    ];
}
