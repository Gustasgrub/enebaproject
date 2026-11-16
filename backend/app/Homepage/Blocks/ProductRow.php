<?php

namespace App\Homepage\Blocks;

use App\Http\Resources\ProductSummaryResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Shared by the providers whose block is a row of product cards. */
class ProductRow
{
    /** Cards per row: enough to fill a wide screen and scroll a little. */
    public const LIMIT = 10;

    /**
     * Products that can be bought: a homepage row is a shop window, so out-of-stock products stay out.
     *
     * @return Builder<Product>
     */
    public static function productsInStock(): Builder
    {
        return Product::query()->withCheapestOffer()->whereNotNull('cheapest_offer.price_cents');
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return list<array<string, mixed>>
     */
    public static function toItems(Collection $products): array
    {
        return ProductSummaryResource::collection($products)->resolve();
    }
}
