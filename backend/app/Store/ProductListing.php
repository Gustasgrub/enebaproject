<?php

namespace App\Store;

use App\Enums\ProductSort;
use App\Models\Product;
use App\Search\SearchService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds the product query behind the store and the search results page: search, filters and sort are
 * all added to one query, which the controller then paginates.
 */
class ProductListing
{
    public const PER_PAGE = 24;

    public const NO_FILTERS = ['platforms' => [], 'regions' => [], 'genres' => [], 'min_price_cents' => null, 'max_price_cents' => null];

    public function __construct(private readonly SearchService $search) {}

    /**
     * @param  array{platforms: list<string>, regions: list<string>, genres: list<string>, min_price_cents: ?int, max_price_cents: ?int}  $filters
     * @return Builder<Product>
     */
    public function query(string $searchText, array $filters, ProductSort $sort): Builder
    {
        $query = Product::query()->withCheapestOffer();

        if ($searchText !== '') {
            $this->search->whereMatches($query, $searchText);
        }

        $query
            ->when($filters['platforms'], fn (Builder $query, array $platforms) => $query->whereIn('platform', $platforms))
            ->when($filters['regions'], fn (Builder $query, array $regions) => $query->whereIn('region', $regions))
            ->when($filters['genres'], fn (Builder $query, array $genres) => $query->whereIn('genre', $genres))
            // A price filter only matches products that are in stock: without stock there is no price.
            ->when($filters['min_price_cents'] !== null, fn (Builder $query) => $query->where('cheapest_offer.price_cents', '>=', $filters['min_price_cents']))
            ->when($filters['max_price_cents'] !== null, fn (Builder $query) => $query->where('cheapest_offer.price_cents', '<=', $filters['max_price_cents']));

        match ($sort) {
            ProductSort::Relevance => $this->search->orderByRelevance($query, $searchText),
            // Products without stock have no price; they go last in both directions.
            ProductSort::PriceAsc => $query->orderByRaw('cheapest_offer.price_cents asc nulls last'),
            ProductSort::PriceDesc => $query->orderByRaw('cheapest_offer.price_cents desc nulls last'),
            ProductSort::Newest => $query->orderByDesc('release_year'),
            ProductSort::Name => $query->orderBy('name'),
        };

        // A unique last sort key keeps the order stable, so no product appears on two pages.
        return $query->orderBy('products.id');
    }
}
