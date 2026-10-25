<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\OrganisationStatus;
use Illuminate\Support\Str;


class Organisation extends Model
{   
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',

    ];
    
    protected function casts(): array
    {
        return [
            'status' => OrganisationStatus::class
        ];
    }
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
    protected static function booted(): void
    {
        static::creating(function ($org) {
            $org->slug ??= Str::slug($org->name) . '-' . Str::lower(Str::random(6));
        });
    }

}
