<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Routine extends Model
{
    protected $fillable = [
        'organisation_id',
        'subject_id',
        'teacher_id',
        'stream_id',
        'semester',
        'day_of_week',
        'period_number',
        'start_time',
        'end_time',
        'room',
        'is_active',
    ];

    protected $casts = [
        'day_of_week'   => DayOfWeek::class,
        'start_time'    => 'datetime:H:i:s',
        'end_time'      => 'datetime:H:i:s',
        'is_active'     => 'boolean',
        'semester'      => 'integer',
        'period_number' => 'integer',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class, 'stream_id', 'code');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}