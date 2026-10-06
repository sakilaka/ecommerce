<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentResultController extends Controller
{
    public function show(Request $request): OrderResource
    {
        $validated = $request->validate([
            'transaction_id' => ['required', 'string', 'max:120'],
        ]);

        $payment = Payment::query()
            ->with(['order.items', 'order.payments'])
            ->where('gateway', 'sslcommerz')
            ->where('gateway_transaction_id', $validated['transaction_id'])
            ->first();

        if (! $payment?->order) {
            throw ValidationException::withMessages([
                'transaction_id' => 'Payment result could not be found.',
            ]);
        }

        return OrderResource::make($payment->order);
    }
}
