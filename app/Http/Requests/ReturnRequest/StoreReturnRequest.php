<?php

namespace App\Http\Requests\ReturnRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReturnRequest extends FormRequest
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
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id))],
            'order_item_id' => ['required', 'integer', Rule::exists('order_items', 'id')],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
