<?php

namespace App\Http\Controllers\Admin;

use App\Homepage\BlockRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomepageBlockRequest;
use App\Models\HomepageBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin: the homepage layout. This is the payoff of the block design: a new row, say "PlayStation
 * hits", is one POST here (a database row), with no code change and no deploy.
 */
class HomepageBlockController extends Controller
{
    /** GET /api/admin/homepage-blocks: every block, disabled ones too, plus the types that can be added. */
    public function index(BlockRegistry $registry): JsonResponse
    {
        return response()->json([
            'data' => HomepageBlock::orderBy('position')->get(['id', 'type', 'title', 'position', 'enabled', 'config']),
            'types' => $registry->types(),
        ]);
    }

    /** POST /api/admin/homepage-blocks: added at the bottom of the page. */
    public function store(HomepageBlockRequest $request): JsonResponse
    {
        $block = HomepageBlock::create([
            ...$request->validated(),
            'config' => $request->validated('config', []),
            'position' => (int) HomepageBlock::max('position') + 1,
        ]);

        return response()->json(['data' => $block], 201);
    }

    /** PATCH /api/admin/homepage-blocks/{block}: title, enabled or config. */
    public function update(HomepageBlockRequest $request, HomepageBlock $block): JsonResponse
    {
        // Saving clears the block's cached data (HomepageBlock::booted), so the change shows at once.
        $block->update($request->validated());

        return response()->json(['data' => $block]);
    }

    /** DELETE /api/admin/homepage-blocks/{block} */
    public function destroy(HomepageBlock $block): Response
    {
        $block->delete();

        return response()->noContent();
    }

    /** POST /api/admin/homepage-blocks/order: {"ids": [3, 1, 2]} sets the order of all blocks at once. */
    public function reorder(Request $request): Response
    {
        $allIds = HomepageBlock::pluck('id')->all();
        $ids = $request->validate([
            // Exactly every block, each once: a partial list would leave positions ambiguous.
            'ids' => ['required', 'array', 'size:'.count($allIds)],
            'ids.*' => ['integer', 'distinct', Rule::in($allIds)],
        ])['ids'];

        DB::transaction(function () use ($ids) {
            foreach ($ids as $index => $id) {
                HomepageBlock::whereKey($id)->update(['position' => $index + 1]);
            }
        });

        return response()->noContent();
    }
}
