<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use RuntimeException;

class PaymentInitiationService
{
    public function __construct(private SslCommerzGateway $sslCommerz) {}

    /**
     * @return array<string, mixed>
     */
    public function initiate(Payment $payment): array
    {
        $payment->loadMissing('order');

        if ($payment->method !== PaymentMethod::SslCommerz) {
            throw new RuntimeException('This payment method does not require gateway initiation.');
        }

        if ($payment->status !== PaymentStatus::Pending) {
            throw new RuntimeException('Only pending payments can be initiated.');
        }

        return $this->sslCommerz->initiate($payment);
    }
}
