<?php

namespace App\Store;

use App\Enums\Platform;
use App\Enums\Region;
use App\Models\Product;
use App\Search\SearchService;
use Illuminate\Database\Eloquent\Builder;

/**
 * The store sidebar's filter options with product counts. Counts follow the current search text only,
 * not the other selected filters (docs/DECISIONS.md #6). Every option is always listed, with zero
 * when nothing matches, so the sidebar does not jump around and a selected option never disappears.
 */
class FilterOptions
{
    public function __construct(private readonly SearchService $search) {}

    /** @return array{platforms: list<array{value: string, count: int}>, regions: list<array{value: string, count: int}>, genres: list<array{value: string, count: int}>} */
    public function forSearch(string $searchText): array
    {
        $matching = Product::query();
        if ($searchText !== '') {
            $this->search->whereMatches($matching, $searchText);
        }

        return [
            'platforms' => $this->countPerValue($matching, 'platform', array_column(Platform::cases(), 'value')),
            'regions' => $this->countPerValue($matching, 'region', array_column(Region::cases(), 'value')),
            'genres' => $this->countPerValue($matching, 'genre', Product::query()->distinct()->orderBy('genre')->pluck('genre')->all()),
        ];
    }

    /**
     * @param  Builder<Product>  $matching
     * @param  list<string>  $allValues
     * @return list<array{value: string, count: int}>
     */
    private function countPerValue(Builder $matching, string $column, array $allValues): array
    {
        $counts = (clone $matching)
            ->toBase()
            ->select($column)
            ->selectRaw('count(*) as product_count')
            ->groupBy($column)
            ->pluck('product_count', $column);

        return array_map(
            fn (string $value) => ['value' => $value, 'count' => (int) ($counts[$value] ?? 0)],
            $allValues,
        );
    }
}
