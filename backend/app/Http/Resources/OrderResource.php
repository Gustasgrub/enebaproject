<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An order with its items and, once paid, the key codes. Expects items.offer.product,
 * items.offer.seller and items.keys to be loaded (Order::DETAIL_RELATIONS).
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isPaid = $this->status === OrderStatus::Paid;

        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'total_cents' => $this->total_cents,
            'payment_reference' => $this->payment_reference,
            'created_at' => $this->created_at->toIso8601String(),
            'items' => $this->items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'seller' => $item->offer->seller->name,
                'product' => [
                    'slug' => $item->offer->product->slug,
                    'name' => $item->offer->product->name,
                    'platform' => $item->offer->product->platform->value,
                    'region' => $item->offer->product->region->value,
                    'image_url' => asset($item->offer->product->image_path),
                ],
                // Key codes are what the buyer paid for: revealed only for paid orders, to their owner.
                'keys' => $isPaid ? $item->keys->map(fn ($key) => $key->code)->values() : [],
            ]),
        ];
    }
}
