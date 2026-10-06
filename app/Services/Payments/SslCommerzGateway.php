<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SslCommerzGateway
{
    public function configured(): bool
    {
        return filled(config('payment_gateways.sslcommerz.store_id'))
            && filled(config('payment_gateways.sslcommerz.store_password'))
            && filled(config('payment_gateways.sslcommerz.init_url'))
            && filled(config('payment_gateways.sslcommerz.validation_url'));
    }

    /**
     * @return array<string, mixed>
     */
    public function initiate(Payment $payment): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('SSLCommerz is enabled but gateway credentials are not configured.');
        }

        $payment->loadMissing(['order.items']);
        $order = $payment->order;
        $transactionId = $payment->gateway_transaction_id ?: $this->transactionId($payment);
        $payload = $this->payload($payment, $transactionId);

        $response = Http::asForm()
            ->connectTimeout((int) config('payment_gateways.sslcommerz.connect_timeout', 5))
            ->timeout((int) config('payment_gateways.sslcommerz.timeout', 15))
            ->post((string) config('payment_gateways.sslcommerz.init_url'), $payload)
            ->throw()
            ->json();

        if (($response['status'] ?? null) !== 'SUCCESS' || blank($response['GatewayPageURL'] ?? null)) {
            throw new RuntimeException((string) ($response['failedreason'] ?? 'SSLCommerz did not return a payment page.'));
        }

        $gatewayPayload = array_merge($payment->gateway_payload ?? [], [
            'sslcommerz' => [
                'tran_id' => $transactionId,
                'sessionkey' => $response['sessionkey'] ?? null,
                'redirect_url' => $response['GatewayPageURL'],
                'initiated_at' => now()->toISOString(),
                'sandbox' => (bool) config('payment_gateways.sslcommerz.sandbox', true),
            ],
        ]);

        $payment->update([
            'gateway' => 'sslcommerz',
            'gateway_transaction_id' => $transactionId,
            'gateway_payload' => $gatewayPayload,
        ]);

        return [
            'payment_id' => $payment->id,
            'order_number' => $order?->order_number,
            'transaction_id' => $transactionId,
            'redirect_url' => $response['GatewayPageURL'],
            'gateway' => 'sslcommerz',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validate(Payment $payment, string $validationId): array
    {
        if (! $this->configured()) {
            throw new RuntimeException('SSLCommerz is enabled but gateway credentials are not configured.');
        }

        $payment->loadMissing('order');

        $response = Http::connectTimeout((int) config('payment_gateways.sslcommerz.connect_timeout', 5))
            ->timeout((int) config('payment_gateways.sslcommerz.timeout', 15))
            ->get((string) config('payment_gateways.sslcommerz.validation_url'), [
                'val_id' => $validationId,
                'store_id' => config('payment_gateways.sslcommerz.store_id'),
                'store_passwd' => config('payment_gateways.sslcommerz.store_password'),
                'v' => 1,
                'format' => 'json',
            ])
            ->throw()
            ->json();

        $this->assertValidResponse($payment, is_array($response) ? $response : []);

        return $response;
    }

    private function transactionId(Payment $payment): string
    {
        return 'AUR-'.$payment->id.'-'.Str::upper(Str::random(8));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Payment $payment, string $transactionId): array
    {
        $order = $payment->order;
        $shipping = $order?->shipping_address ?? [];

        return [
            'store_id' => config('payment_gateways.sslcommerz.store_id'),
            'store_passwd' => config('payment_gateways.sslcommerz.store_password'),
            'total_amount' => number_format($payment->amount / 100, 2, '.', ''),
            'currency' => 'BDT',
            'tran_id' => $transactionId,
            'success_url' => url((string) config('payment_gateways.sslcommerz.success_url')),
            'fail_url' => url((string) config('payment_gateways.sslcommerz.fail_url')),
            'cancel_url' => url((string) config('payment_gateways.sslcommerz.cancel_url')),
            'ipn_url' => url((string) config('payment_gateways.sslcommerz.ipn_url')),
            'cus_name' => $order?->customer_name,
            'cus_email' => $order?->customer_email ?: 'customer@example.com',
            'cus_phone' => $order?->customer_phone,
            'cus_add1' => $shipping['address_line'] ?? $order?->shipping_area ?? $order?->shipping_district ?? 'Bangladesh',
            'cus_city' => $order?->shipping_district ?? 'Dhaka',
            'cus_country' => 'Bangladesh',
            'shipping_method' => 'Courier',
            'product_name' => $order?->items?->pluck('product_name')->take(3)->implode(', ') ?: 'Aurelia Order',
            'product_category' => 'Ecommerce',
            'product_profile' => 'general',
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function assertValidResponse(Payment $payment, array $response): void
    {
        $status = (string) ($response['status'] ?? '');

        if (! in_array($status, ['VALID', 'VALIDATED'], true)) {
            throw new RuntimeException('SSLCommerz validation did not confirm a successful transaction.');
        }

        if (($response['tran_id'] ?? null) !== $payment->gateway_transaction_id) {
            throw new RuntimeException('SSLCommerz validation transaction id does not match this payment.');
        }

        if (($response['currency'] ?? null) !== 'BDT') {
            throw new RuntimeException('SSLCommerz validation currency does not match this order.');
        }

        $expectedAmount = number_format($payment->amount / 100, 2, '.', '');
        $actualAmount = number_format((float) ($response['amount'] ?? 0), 2, '.', '');

        if ($actualAmount !== $expectedAmount) {
            throw new RuntimeException('SSLCommerz validation amount does not match this order.');
        }
    }
}
