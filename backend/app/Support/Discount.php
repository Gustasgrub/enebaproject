<?php

namespace App\Support;

class Discount
{
    /**
     * Whole-number percentage saved compared with the original price, e.g. 2700 of 5999 → 55.
     * Null when there is no original price or it is not higher than the price.
     */
    public static function percent(int $priceCents, ?int $originalPriceCents): ?int
    {
        if ($originalPriceCents === null || $originalPriceCents <= $priceCents) {
            return null;
        }

        return (int) round(100 - 100 * $priceCents / $originalPriceCents);
    }
}
