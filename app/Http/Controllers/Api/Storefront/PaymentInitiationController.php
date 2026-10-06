<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentInitiationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentInitiationController extends Controller
{
    public function store(Request $request, Payment $payment, PaymentInitiationService $payments): JsonResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:80'],
            'customer_phone' => ['required', 'regex:/^01[3-9][0-9]{8}$/'],
        ]);

        $payment->loadMissing('order');

        if (! $payment->order
            || $payment->order->order_number !== $validated['order_number']
            || $payment->order->customer_phone !== $validated['customer_phone']) {
            abort(404);
        }

        try {
            return response()->json([
                'data' => $payments->initiate($payment),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
