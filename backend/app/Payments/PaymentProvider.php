<?php

namespace App\Payments;

/**
 * Charges a buyer. The backend only ever receives a payment token created in the browser, never a card
 * number (docs/DECISIONS.md #9). The only implementation is FakePaymentProvider; a real provider such
 * as Stripe would use payment intents and webhooks, so this interface would grow, but the boundary
 * stays the same: the server sees a token, not a card.
 */
interface PaymentProvider
{
    public function charge(int $amountCents, string $paymentToken): PaymentResult;
}
