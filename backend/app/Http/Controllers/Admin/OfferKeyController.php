<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\ProductKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Admin: stock. In a real marketplace sellers upload their own keys; here an admin does it for them. */
class OfferKeyController extends Controller
{
    /** POST /api/admin/offers/{offer}/keys: {"codes": ["AAAAA-BBBBB-CCCCC", …]} adds available keys. */
    public function store(Request $request, Offer $offer): JsonResponse
    {
        $codes = $request->validate([
            'codes' => ['required', 'array', 'min:1', 'max:500'],
            'codes.*' => ['required', 'string', 'distinct', 'max:100'],
        ])['codes'];

        DB::transaction(function () use ($offer, $codes) {
            foreach ($codes as $code) {
                // Through the model, so the `encrypted` cast encrypts every code.
                ProductKey::create(['offer_id' => $offer->id, 'code' => trim($code)]);
            }
        });

        return response()->json(['data' => ['added' => count($codes), 'stock' => $offer->availableKeys()->count()]], 201);
    }
}
