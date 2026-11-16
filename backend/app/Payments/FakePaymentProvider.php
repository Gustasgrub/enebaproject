<?php

namespace App\Payments;

use Illuminate\Support\Str;

/**
 * A stand-in for a card processor. The frontend's card form turns the two documented test cards into
 * these tokens, as Stripe Elements does with real cards: 4242 4242 4242 4242 → tok_success,
 * 4000 0000 0000 0002 → tok_declined. No money moves.
 */
class FakePaymentProvider implements PaymentProvider
{
    public function charge(int $amountCents, string $paymentToken): PaymentResult
    {
        return match ($paymentToken) {
            'tok_success' => PaymentResult::succeeded('fake_ch_'.Str::lower(Str::random(16))),
            'tok_declined' => PaymentResult::declined('Your card was declined. Try another card.'),
            default => PaymentResult::declined('The payment details were not recognised. Try again.'),
        };
    }
}
