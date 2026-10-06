<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\AuditLog;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManualPaymentConfirmationController extends Controller
{
    public function store(Request $request, Payment $payment): PaymentResource
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:80'],
            'customer_phone' => ['required', 'regex:/^01[3-9][0-9]{8}$/'],
            'sender_phone' => ['required', 'regex:/^01[3-9][0-9]{8}$/'],
            'transaction_id' => ['required', 'string', 'min:6', 'max:120'],
            'method' => ['required', Rule::in([PaymentMethod::Bkash->value, PaymentMethod::Nagad->value])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $payment = DB::transaction(function () use ($payment, $request, $validated): Payment {
            $payment = Payment::query()
                ->with('order')
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $payment->order
                || $payment->order->order_number !== $validated['order_number']
                || $payment->order->customer_phone !== $validated['customer_phone']) {
                abort(404);
            }

            if (! in_array($payment->method, [PaymentMethod::Bkash, PaymentMethod::Nagad], true)
                || $payment->method->value !== $validated['method']) {
                throw ValidationException::withMessages([
                    'method' => 'This payment does not match the selected mobile payment method.',
                ]);
            }

            if ($payment->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending mobile payments can receive transaction proof.',
                ]);
            }

            $payload = $payment->gateway_payload ?? [];
            $payload['manual_submission'] = [
                'method' => $validated['method'],
                'sender_phone' => $validated['sender_phone'],
                'transaction_id' => $validated['transaction_id'],
                'note' => $validated['note'] ?? null,
                'status' => 'submitted_for_review',
                'submitted_at' => now()->toISOString(),
            ];

            $payment->update([
                'gateway_transaction_id' => $validated['transaction_id'],
                'gateway_payload' => $payload,
            ]);

            AuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'event' => 'payment.manual-proof.submitted',
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->id,
                'old_values' => null,
                'new_values' => [
                    'method' => $validated['method'],
                    'sender_phone' => $validated['sender_phone'],
                    'transaction_id' => $validated['transaction_id'],
                    'order_number' => $payment->order->order_number,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return $payment;
        });

        return PaymentResource::make($payment->refresh());
    }
}
