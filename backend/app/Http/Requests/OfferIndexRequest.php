<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OfferIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'max:50'],
            'ids.*' => ['integer', 'min:1'],
        ];
    }
}
