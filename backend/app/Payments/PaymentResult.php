<?php

namespace App\Payments;

final readonly class PaymentResult
{
    private function __construct(
        public bool $succeeded,
        /** The provider's id for the charge, stored on the order. */
        public ?string $reference,
        /** Shown to the buyer when the payment did not go through. */
        public ?string $failureMessage,
    ) {}

    public static function succeeded(string $reference): self
    {
        return new self(true, $reference, null);
    }

    public static function declined(string $failureMessage): self
    {
        return new self(false, null, $failureMessage);
    }
}
