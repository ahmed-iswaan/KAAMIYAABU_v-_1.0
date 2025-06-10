<?php

namespace App\Enums;

enum FineInterval: string
{
    case HOURLY  = 'hourly';
    case DAILY   = 'daily';
    case MONTHLY = 'monthly';
}
