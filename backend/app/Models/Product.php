<?php

namespace App\Models;

use App\Enums\Platform;
use App\Enums\Region;
use App\Search\SearchNormalizer;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One game on one platform in one region, sold through several sellers' offers. */
#[Fillable([
    'slug', 'name', 'description', 'image_path', 'platform', 'region', 'genre', 'release_year', 'search_keywords',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        // search_text is derived from name and keywords. Recomputing it on every save means it can never
        // fall out of date, and it uses the same normalizer as search queries (docs/DECISIONS.md #4).
        static::saving(function (Product $product) {
            $product->search_text = SearchNormalizer::normalize("{$product->name} {$product->search_keywords}");
        });
    }

    protected function casts(): array
    {
        return [
            'platform' => Platform::class,
            'region' => Region::class,
            'release_year' => 'integer',
        ];
    }

    /** @return HasMany<Offer, $this> */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}
