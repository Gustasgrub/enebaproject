<?php

namespace App\Http\Resources;

use App\Models\Offer;
use App\Support\Discount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One seller's offer: price, stock and seller. Expects `seller` loaded and `available_keys_count`
 * counted (Offer::withCount('availableKeys')); `product` is included when loaded (cart page).
 *
 * @mixin Offer
 */
class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price_cents' => $this->price_cents,
            'original_price_cents' => $this->original_price_cents,
            'discount_percent' => Discount::percent($this->price_cents, $this->original_price_cents),
            'stock' => $this->available_keys_count,
            'seller' => [
                'name' => $this->seller->name,
                'rating' => $this->seller->rating,
            ],
            'product' => $this->whenLoaded('product', fn () => [
                'slug' => $this->product->slug,
                'name' => $this->product->name,
                'image_url' => asset($this->product->image_path),
                'platform' => $this->product->platform->value,
                'region' => $this->product->region->value,
            ]),
        ];
    }
}
