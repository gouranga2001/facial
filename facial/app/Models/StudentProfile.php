<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'roll_number',
        'batch',
        'semester'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}