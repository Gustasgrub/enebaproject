<?php

namespace App\Enums;

/** Where a product's key is activated. */
enum Platform: string
{
    case Steam = 'Steam';
    case PlayStation = 'PlayStation';
    case Xbox = 'Xbox';
    case Nintendo = 'Nintendo';
    case EaApp = 'EA App';
    case UbisoftConnect = 'Ubisoft Connect';
}
