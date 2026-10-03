<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stream extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;   // streams has no created_at/updated_at

    protected $fillable = ['code', 'name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'stream_id', 'code');
    }

    public function routines(): HasMany
    {
        return $this->hasMany(Routine::class, 'stream_id', 'code');
    }
}