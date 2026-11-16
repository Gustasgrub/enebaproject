<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterOptionsRequest;
use App\Store\FilterOptions;
use Illuminate\Http\JsonResponse;

class FilterOptionsController extends Controller
{
    /** GET /api/filters?q=: the store sidebar's options with product counts for the current search. */
    public function __invoke(FilterOptionsRequest $request, FilterOptions $filterOptions): JsonResponse
    {
        return response()->json([
            'data' => $filterOptions->forSearch(trim((string) $request->validated('q'))),
        ]);
    }
}
