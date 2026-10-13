<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Subject extends Model
{
    protected $fillable = [
        'organisation_id',
        'name',
        'code'
    ];

    public function Organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

}
