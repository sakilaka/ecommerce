<?php

namespace App\Http\Requests\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['nullable', 'integer', Rule::exists('orders', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id))],
            'topic' => ['required', 'string', Rule::in(['order_help', 'delivery', 'return_refund', 'payment', 'product', 'account'])],
            'priority' => ['nullable', 'string', Rule::in(['normal', 'high'])],
            'subject' => ['required', 'string', 'min:5', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
