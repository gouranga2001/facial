<?php

namespace App\Models;

use App\Enums\StreamStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Stream extends Model
{
    protected $fillable = ['organisation_id', 'code', 'name'];

    protected function casts(): array
    {
        return ['status' => StreamStatus::class];
    }

    protected static function booted(): void
    {
        static::creating(function (Stream $stream) {
            $stream->uuid ??= (string) Str::uuid();
        });
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'stream_id');
    }

    public function students(): HasMany
    {
        return $this->users()->whereHas('studentProfile');
    }

    public function routines(): HasMany
    {
        return $this->hasMany(Routine::class, 'stream_id');
    }
}