<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class TrackOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'regex:/^01[3-9][0-9]{8}$/'],
        ];
    }
}
