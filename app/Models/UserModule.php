<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserModule extends Model
{
    public $incrementing = true;
    
    protected $fillable = [
        'user_id',
        'module_id',
        'is_valid',
        'is_editable'
    ];
}
