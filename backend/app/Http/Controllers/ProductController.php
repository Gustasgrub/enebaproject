<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductIndexRequest;
use App\Http\Resources\ProductSummaryResource;
use App\Search\SearchService;
use App\Store\ProductListing;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /** GET /api/products: the store and the search results page, one page of 24 at a time. */
    public function index(ProductIndexRequest $request, ProductListing $listing, SearchService $search): AnonymousResourceCollection
    {
        $searchText = $request->searchText();

        $products = $listing->query($searchText, $request->filters(), $request->sort())
            ->paginate(ProductListing::PER_PAGE)
            ->withQueryString();

        // Only worth the extra query when a search found nothing at all.
        $didYouMean = $searchText !== '' && $products->total() === 0
            ? $search->closestProductName($searchText)
            : null;

        return ProductSummaryResource::collection($products)
            ->additional(['did_you_mean' => $didYouMean]);
    }
}
