<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductIndexRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductSummaryResource;
use App\Models\Product;
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

    /** GET /api/products/{slug}: the product page, with every seller's offer and its stock. */
    public function show(Product $product): ProductDetailResource
    {
        // Two eager-loaded queries for all offers, sellers and stock counts, instead of one per offer.
        $product->load(['offers' => fn ($offers) => $offers->with('seller')->withCount('availableKeys')]);

        return new ProductDetailResource($product);
    }
}
