<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /** GET /api/orders: the logged-in user's orders, newest first, with their keys. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()->orders()
            ->with(Order::DETAIL_RELATIONS)
            ->latest()
            ->orderByDesc('id')
            ->get();

        return OrderResource::collection($orders);
    }

    /** GET /api/orders/{order}: one of the user's own orders. */
    public function show(Request $request, Order $order): OrderResource
    {
        // 404 rather than 403: someone else's order should not even be confirmed to exist.
        abort_unless($order->user_id === $request->user()->id, 404);

        return new OrderResource($order->loadDetails());
    }
}
