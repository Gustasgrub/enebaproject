<?php

namespace App\Enums;

/**
 * Lifecycle of a product key: available → reserved (checkout started) → sold (payment succeeded).
 * A declined payment puts a reserved key back to available.
 */
enum KeyStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Sold = 'sold';
}
