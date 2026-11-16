<?php

namespace App\Http\Controllers;

use App\Checkout\CheckoutService;
use App\Checkout\OutOfStockException;
use App\Enums\OrderStatus;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    /**
     * POST /api/checkout. Answers 201 with the paid order and its keys, 402 when the payment was
     * declined (the order is kept as failed, the keys go back on sale), or 409 when an item does not
     * have enough keys (nothing is reserved or charged).
     */
    public function __invoke(CheckoutRequest $request, CheckoutService $checkout): JsonResponse
    {
        try {
            $order = $checkout->checkout($request->user(), $request->items(), $request->validated('payment_token'));
        } catch (OutOfStockException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'offer_id' => $exception->offer->id], 409);
        }

        $order->loadDetails();

        if ($order->status !== OrderStatus::Paid) {
            return response()->json([
                'message' => 'Your card was declined. Try another card.',
                'data' => new OrderResource($order),
            ], 402);
        }

        return (new OrderResource($order))->response()->setStatusCode(201);
    }
}
