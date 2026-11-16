<?php

namespace App\Models;

use App\Enums\KeyStatus;
use Database\Factories\ProductKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A game key a buyer activates on the product's platform. */
#[Fillable(['offer_id', 'code', 'status', 'order_item_id'])]
// The code is what the buyer pays for, so it never appears when a key is turned into JSON by
// accident. Only the order pages read it, explicitly.
#[Hidden(['code'])]
class ProductKey extends Model
{
    /** @use HasFactory<ProductKeyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            // Stored encrypted with APP_KEY; decrypted automatically when read.
            'code' => 'encrypted',
            'status' => KeyStatus::class,
        ];
    }

    /** @return BelongsTo<Offer, $this> */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /** @return BelongsTo<OrderItem, $this> */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
