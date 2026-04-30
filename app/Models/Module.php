<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'url',
        'icon',
    ];

    // Relationships
    public function userModules(): HasMany
    {
        return $this->hasMany(UserModule::class);
    }
}
