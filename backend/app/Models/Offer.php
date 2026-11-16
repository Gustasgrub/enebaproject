<?php

namespace App\Models;

use App\Enums\KeyStatus;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One seller's listing of one product, with its own price and its own keys. */
#[Fillable(['product_id', 'seller_id', 'price_cents', 'original_price_cents'])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'original_price_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Seller, $this> */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    /** @return HasMany<ProductKey, $this> */
    public function keys(): HasMany
    {
        return $this->hasMany(ProductKey::class);
    }

    /**
     * The offer's stock: there is no stock column, so it cannot disagree with the keys.
     * Count it with Offer::withCount('availableKeys').
     *
     * @return HasMany<ProductKey, $this>
     */
    public function availableKeys(): HasMany
    {
        return $this->keys()->where('status', KeyStatus::Available);
    }
}
