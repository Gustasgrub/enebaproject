<?php

namespace App\Http\Requests;

use App\Enums\Platform;
use App\Enums\ProductSort;
use App\Enums\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Query parameters of GET /api/products: search, filters, sort and page. */
class ProductIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'platform' => ['array'],
            'platform.*' => [Rule::enum(Platform::class)],
            'region' => ['array'],
            'region.*' => [Rule::enum(Region::class)],
            'genre' => ['array'],
            'genre.*' => ['string', 'max:50'],
            // Euros, as typed in the store's price range inputs.
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'sort' => ['nullable', Rule::enum(ProductSort::class)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function searchText(): string
    {
        return trim((string) $this->validated('q'));
    }

    /** @return array{platforms: list<string>, regions: list<string>, genres: list<string>, min_price_cents: ?int, max_price_cents: ?int} */
    public function filters(): array
    {
        return [
            'platforms' => $this->validated('platform', []),
            'regions' => $this->validated('region', []),
            'genres' => $this->validated('genre', []),
            'min_price_cents' => $this->euroParameterInCents('min_price'),
            'max_price_cents' => $this->euroParameterInCents('max_price'),
        ];
    }

    /** Relevance needs a search query to rank by; without one the store shows the newest games first. */
    public function sort(): ProductSort
    {
        $sort = ProductSort::tryFrom((string) $this->validated('sort'));

        if ($sort === null || ($sort === ProductSort::Relevance && $this->searchText() === '')) {
            return $this->searchText() === '' ? ProductSort::Newest : ProductSort::Relevance;
        }

        return $sort;
    }

    private function euroParameterInCents(string $key): ?int
    {
        $euros = $this->validated($key);

        return $euros === null ? null : (int) round($euros * 100);
    }
}
