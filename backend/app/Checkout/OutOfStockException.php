<?php

namespace App\Checkout;

use App\Models\Offer;
use RuntimeException;

/** Fewer keys are available than the buyer wants of one offer. Nothing has been reserved or charged. */
class OutOfStockException extends RuntimeException
{
    public function __construct(public readonly Offer $offer, public readonly int $availableCount)
    {
        $product = $offer->product;

        parent::__construct(sprintf(
            'Only %d %s left of %s (%s, %s) from %s. Lower the quantity or choose another seller.',
            $availableCount,
            $availableCount === 1 ? 'key is' : 'keys are',
            $product->name,
            $product->platform->value,
            $product->region->value,
            $offer->seller->name,
        ));
    }
}
