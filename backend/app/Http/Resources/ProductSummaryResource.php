<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Support\Discount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A product as shown on a card (store grid, search results, homepage rows). Expects the query to have
 * used Product::withCheapestOffer(), which supplies the price.
 *
 * @mixin Product
 */
class ProductSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $priceCents = $this->cheapest_price_cents === null ? null : (int) $this->cheapest_price_cents;
        $originalPriceCents = $this->cheapest_original_price_cents === null ? null : (int) $this->cheapest_original_price_cents;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'image_url' => asset($this->image_path),
            'platform' => $this->platform->value,
            'region' => $this->region->value,
            'genre' => $this->genre,
            'release_year' => $this->release_year,
            // The cheapest offer that has stock; null means the product is out of stock.
            'price_cents' => $priceCents,
            'original_price_cents' => $originalPriceCents,
            'discount_percent' => $priceCents === null ? null : Discount::percent($priceCents, $originalPriceCents),
        ];
    }
}
