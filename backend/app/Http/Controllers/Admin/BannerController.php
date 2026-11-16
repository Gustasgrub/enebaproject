<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\HomepageBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Admin: the hero carousel's slides. */
class BannerController extends Controller
{
    /** GET /api/admin/banners: every banner, inactive ones too. */
    public function index(): JsonResponse
    {
        return response()->json(['data' => Banner::orderBy('position')->get()]);
    }

    /** PATCH /api/admin/banners/{banner}: switch on or off, or change the texts and link. */
    public function update(Request $request, Banner $banner): JsonResponse
    {
        $banner->update($request->validate([
            'active' => ['sometimes', 'boolean'],
            'title' => ['sometimes', 'string', 'max:100'],
            'subtitle' => ['sometimes', 'string', 'max:200'],
            // A path inside the frontend only, so a banner cannot send visitors to another site.
            'link_url' => ['sometimes', 'string', 'max:255', 'regex:/^\/(?!\/)/'],
        ]));

        // The banner blocks cache their slides; clear them so the change shows immediately.
        HomepageBlock::where('type', 'banner')->get()->each->forgetCachedData();

        return response()->json(['data' => $banner]);
    }
}
