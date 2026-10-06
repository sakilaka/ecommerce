<?php

namespace App\Http\Requests\Checkout;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutRequest extends FormRequest
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
            'coupon_code' => ['nullable', 'string', 'max:60'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'customer_address_id' => ['nullable', 'integer', Rule::exists('customer_addresses', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id))],
            'customer.name' => ['required', 'string', 'max:120'],
            'customer.email' => ['nullable', 'email', 'max:160'],
            'customer.phone' => ['required', 'regex:/^01[3-9][0-9]{8}$/'],
            'shipping.label' => ['nullable', 'string', 'max:40'],
            'shipping.district' => ['required_without:customer_address_id', 'string', 'max:80'],
            'shipping.area' => ['nullable', 'string', 'max:120'],
            'shipping.postal_code' => ['nullable', 'string', 'max:20'],
            'shipping.address_line' => ['required_without:customer_address_id', 'string', 'max:500'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
