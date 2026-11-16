<?php

namespace App\Enums;

/** Where a product's key can be activated. */
enum Region: string
{
    case Global = 'Global';
    case Europe = 'Europe';
    case NorthAmerica = 'North America';
}
