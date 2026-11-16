<?php

namespace App\Http\Controllers;

use App\Enums\ProductSort;
use App\Http\Requests\SearchSuggestionRequest;
use App\Http\Resources\ProductSummaryResource;
use App\Store\ProductListing;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchSuggestionController extends Controller
{
    public const LIMIT = 8;

    /** GET /api/search/suggest?q=: the best matches for the header's live suggestions dropdown. */
    public function __invoke(SearchSuggestionRequest $request, ProductListing $listing): AnonymousResourceCollection
    {
        $searchText = trim((string) $request->validated('q'));

        if ($searchText === '') {
            return ProductSummaryResource::collection([]);
        }

        $products = $listing->query($searchText, ProductListing::NO_FILTERS, ProductSort::Relevance)
            ->limit(self::LIMIT)
            ->get();

        return ProductSummaryResource::collection($products);
    }
}
