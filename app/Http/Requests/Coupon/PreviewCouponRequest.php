<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class PreviewCouponRequest extends FormRequest
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
            'cart_session_id' => ['nullable', 'string', 'max:255'],
            'coupon_code' => ['required', 'string', 'max:60'],
        ];
    }
}
