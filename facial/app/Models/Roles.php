<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Roles extends Model
{
    const UPDATED_AT = null;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}