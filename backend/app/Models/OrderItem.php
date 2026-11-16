<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One line of an order: an offer, how many, and the unit price paid. */
#[Fillable(['order_id', 'offer_id', 'quantity', 'unit_price_cents'])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Offer, $this> */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * The keys delivered for this line, one per unit of quantity.
     *
     * @return HasMany<ProductKey, $this>
     */
    public function keys(): HasMany
    {
        return $this->hasMany(ProductKey::class);
    }
}
