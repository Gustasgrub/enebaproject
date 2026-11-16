<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The product page: details plus every seller's offer. Offers in stock come first, cheapest first,
 * so the first offer is the one to highlight; offers without stock follow.
 *
 * @mixin Product
 */
class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $offers = $this->offers
            ->sortBy([
                fn ($a, $b) => ($b->available_keys_count > 0) <=> ($a->available_keys_count > 0),
                ['price_cents', 'asc'],
            ])
            ->values();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => asset($this->image_path),
            'platform' => $this->platform->value,
            'region' => $this->region->value,
            'genre' => $this->genre,
            'release_year' => $this->release_year,
            'offers' => OfferResource::collection($offers),
        ];
    }
}
