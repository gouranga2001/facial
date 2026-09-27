<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\OrganisationStatus;


class Organisation extends Model
{   
    use HasUuids;

    protected $fillable = [
        'uuid',
        'name',
        'slug',

    ];
    
    protected function casts(): array
    {
        return [
            'status' => OrganisationStatus::class
        ];
    }
}
