<?php

namespace App\Enums;

enum StreamStatus: string
{
    case ACTIVE   = 'active';
    case INACTIVE = 'inactive';
}