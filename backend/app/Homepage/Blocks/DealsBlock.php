<?php

namespace App\Homepage\Blocks;

/**
 * `deals`: products whose cheapest offer is discounted, biggest discount first. Using the cheapest
 * offer (rather than any discounted offer) keeps the row consistent with the price on the card.
 */
class DealsBlock implements BlockProvider
{
    public function getData(array $config): array
    {
        $deals = ProductRow::productsInStock()
            ->whereColumn('cheapest_offer.original_price_cents', '>', 'cheapest_offer.price_cents')
            // Smallest price/original ratio = biggest discount.
            ->orderByRaw('cheapest_offer.price_cents::numeric / cheapest_offer.original_price_cents')
            ->orderBy('products.id')
            ->limit(ProductRow::LIMIT)
            ->get();

        return ProductRow::toItems($deals);
    }
}
