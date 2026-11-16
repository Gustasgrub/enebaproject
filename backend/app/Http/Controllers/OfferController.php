<?php

namespace App\Http\Controllers;

use App\Http\Requests\OfferIndexRequest;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OfferController extends Controller
{
    /**
     * GET /api/offers?ids[]=: current price and stock of the offers in a cart. The cart in the browser
     * stores only offer ids and quantities, so what it shows always comes from here, never from a
     * price saved earlier in the browser.
     */
    public function index(OfferIndexRequest $request): AnonymousResourceCollection
    {
        $offers = Offer::query()
            ->whereIn('id', $request->validated('ids'))
            ->with(['seller', 'product'])
            ->withCount('availableKeys')
            ->get();

        return OfferResource::collection($offers);
    }
}
