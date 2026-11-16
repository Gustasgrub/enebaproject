<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** POST /api/checkout: offers and quantities only. Prices are read from the database, never sent. */
class CheckoutRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.offer_id' => ['required', 'integer', 'distinct', 'exists:offers,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10'],
            // A token from the card form, e.g. "tok_success"; never a card number.
            'payment_token' => ['required', 'string', 'max:100'],
        ];
    }

    /** @return list<array{offer_id: int, quantity: int}> */
    public function items(): array
    {
        return array_map(
            fn (array $item) => ['offer_id' => (int) $item['offer_id'], 'quantity' => (int) $item['quantity']],
            $this->validated('items'),
        );
    }
}
