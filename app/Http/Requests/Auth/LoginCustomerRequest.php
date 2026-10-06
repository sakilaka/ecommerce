<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginCustomerRequest extends FormRequest
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
            'login' => ['required', 'string', 'max:160'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
            'cart_session_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
