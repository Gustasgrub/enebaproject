<?php

namespace App\Http\Controllers;

use App\Homepage\BlockRegistry;
use App\Models\HomepageBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * The homepage comes in separate requests: first the layout, then one request per block. That
 * separation is what isolates the blocks: a block whose provider throws fails only its own request
 * (Laravel logs the exception and answers 500), and with several FrankenPHP threads a slow block does
 * not hold up the others (PLAN.md, Section 7).
 */
class HomepageController extends Controller
{
    /** Seconds a block's data is reused. Short, because prices and stock change as keys sell. */
    public const CACHE_SECONDS = 60;

    /** GET /api/homepage: the enabled blocks, in order. */
    public function layout(): JsonResponse
    {
        $blocks = HomepageBlock::query()
            ->where('enabled', true)
            ->orderBy('position')
            ->get(['id', 'type', 'title']);

        return response()->json(['data' => $blocks]);
    }

    /** GET /api/homepage/blocks/{block}: one block's items. */
    public function block(HomepageBlock $block, BlockRegistry $registry): JsonResponse
    {
        abort_unless($block->enabled, 404);
        $provider = $registry->providerFor($block->type) ?? abort(404, "No provider for block type '{$block->type}'.");

        // Saving or deleting a block (or one of its banners) clears this entry, so admin edits show at once.
        $items = Cache::remember($block->cacheKey(), self::CACHE_SECONDS, fn () => $provider->getData($block->config));

        return response()->json(['data' => $items]);
    }
}
