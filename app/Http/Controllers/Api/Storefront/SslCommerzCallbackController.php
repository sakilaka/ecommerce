<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\SslCommerzGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SslCommerzCallbackController extends Controller
{
    public function success(Request $request, SslCommerzGateway $gateway): JsonResponse|RedirectResponse
    {
        return $this->validateAndRecordCallback($request, $gateway, 'success_validated');
    }

    public function fail(Request $request): JsonResponse|RedirectResponse
    {
        return $this->recordCallback($request, PaymentStatus::Failed, 'failed');
    }

    public function cancel(Request $request): JsonResponse|RedirectResponse
    {
        return $this->recordCallback($request, PaymentStatus::Failed, 'cancelled');
    }

    public function ipn(Request $request, SslCommerzGateway $gateway): JsonResponse
    {
        return $this->validateAndRecordCallback($request, $gateway, 'ipn_validated');
    }

    private function validateAndRecordCallback(Request $request, SslCommerzGateway $gateway, string $event): JsonResponse|RedirectResponse
    {
        $transactionId = $request->string('tran_id')->toString();
        $validationId = $request->string('val_id')->toString();

        if ($transactionId === '') {
            return response()->json(['message' => 'Missing SSLCommerz transaction id.'], 422);
        }

        if ($validationId === '') {
            return response()->json(['message' => 'Missing SSLCommerz validation id.'], 422);
        }

        $payment = $this->findPayment($transactionId);

        try {
            $validation = $gateway->validate($payment, $validationId);
        } catch (RuntimeException $exception) {
            $this->storeCallbackPayload($payment, $request, PaymentStatus::Failed, 'validation_failed', [
                'error' => $exception->getMessage(),
            ]);

            if (! $request->expectsJson()) {
                return $this->resultResponse($request, $payment, PaymentStatus::Failed, 'validation_failed');
            }

            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $this->storeCallbackPayload($payment, $request, PaymentStatus::Paid, $event, [
            'validation' => $this->sanitizePayload($validation),
        ]);

        return $this->resultResponse($request, $payment, PaymentStatus::Paid, $event);
    }

    private function recordCallback(Request $request, PaymentStatus $status, string $event): JsonResponse|RedirectResponse
    {
        $transactionId = $request->string('tran_id')->toString();

        if ($transactionId === '') {
            return response()->json(['message' => 'Missing SSLCommerz transaction id.'], 422);
        }

        $payment = $this->findPayment($transactionId);

        $this->storeCallbackPayload($payment, $request, $status, $event);

        return $this->resultResponse($request, $payment, $status, $event);
    }

    private function resultResponse(Request $request, Payment $payment, PaymentStatus $status, string $event): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'data' => [
                    'payment_id' => $payment->id,
                    'order_number' => $payment->order?->order_number,
                    'status' => $status->value,
                    'event' => $event,
                ],
            ]);
        }

        return redirect('/payment/result?'.http_build_query([
            'transaction_id' => $payment->gateway_transaction_id,
            'status' => $status->value,
            'event' => $event,
        ]));
    }

    private function findPayment(string $transactionId): Payment
    {
        return Payment::query()
            ->with('order')
            ->where('gateway', 'sslcommerz')
            ->where('gateway_transaction_id', $transactionId)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function storeCallbackPayload(Payment $payment, Request $request, PaymentStatus $status, string $event, array $extra = []): void
    {
        DB::transaction(function () use ($payment, $request, $status, $event, $extra): void {
            $payload = array_merge($payment->gateway_payload ?? [], [
                'sslcommerz_callback' => array_merge([
                    'event' => $event,
                    'payload' => $this->sanitizePayload($request->all()),
                    'received_at' => now()->toISOString(),
                    'validation_required' => false,
                ], $extra),
            ]);

            $payment->update([
                'status' => $status,
                'gateway_payload' => $payload,
                'paid_at' => $status === PaymentStatus::Paid ? now() : null,
            ]);

            $payment->order?->update([
                'payment_status' => $status,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        unset($payload['store_passwd'], $payload['store_password']);

        return $payload;
    }
}
