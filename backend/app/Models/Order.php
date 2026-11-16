<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'status', 'total_cents', 'payment_reference'])]
class Order extends Model
{
    /** Everything OrderResource shows, eager-loaded in a fixed number of queries. */
    public const DETAIL_RELATIONS = ['items.offer.product', 'items.offer.seller', 'items.keys'];

    public function loadDetails(): static
    {
        return $this->load(self::DETAIL_RELATIONS);
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
