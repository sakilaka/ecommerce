<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\StoreSetting;
use Throwable;

class PaymentMethodCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $settings = $this->settingValues();

        $methods = collect(config('payment_methods.methods', []))
            ->map(fn (array $method, string $value): array => $this->normalize($value, $this->applySettings($value, $method, $settings)))
            ->sortBy('sort')
            ->values()
            ->all();

        return $methods;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(PaymentMethod $method): ?array
    {
        $definition = config("payment_methods.methods.{$method->value}");

        return is_array($definition)
            ? $this->normalize($method->value, $this->applySettings($method->value, $definition, $this->settingValues()))
            : null;
    }

    public function isCheckoutAvailable(PaymentMethod $method): bool
    {
        return (bool) ($this->find($method)['enabled'] ?? false);
    }

    public function isCheckoutReady(PaymentMethod $method): bool
    {
        $definition = $this->find($method);

        return (bool) ($definition['enabled'] ?? false) && ($definition['checkout_ready'] ?? false) === true;
    }

    public function initialStatus(PaymentMethod $method): PaymentStatus
    {
        return $method === PaymentMethod::CashOnDelivery
            ? PaymentStatus::Unpaid
            : PaymentStatus::Pending;
    }

    /**
     * @param  array<string, mixed>  $method
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function applySettings(string $value, array $method, array $settings): array
    {
        $enabledKey = match ($value) {
            PaymentMethod::CashOnDelivery->value => 'checkout.cod_enabled',
            PaymentMethod::SslCommerz->value => 'checkout.sslcommerz_enabled',
            PaymentMethod::Bkash->value => 'checkout.bkash_enabled',
            PaymentMethod::Nagad->value => 'checkout.nagad_enabled',
            PaymentMethod::Card->value => 'checkout.card_enabled',
            default => null,
        };

        if ($enabledKey !== null && array_key_exists($enabledKey, $settings)) {
            $method['enabled'] = (bool) $settings[$enabledKey];
        }

        if ($value === PaymentMethod::Bkash->value) {
            $method = $this->applyManualMobileSettings($method, $settings, 'bkash');
        }

        if ($value === PaymentMethod::Nagad->value) {
            $method = $this->applyManualMobileSettings($method, $settings, 'nagad');
        }

        return $method;
    }

    /**
     * @param  array<string, mixed>  $method
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function applyManualMobileSettings(array $method, array $settings, string $provider): array
    {
        foreach (['merchant_number', 'account_type'] as $field) {
            $key = "checkout.{$provider}_{$field}";

            if (array_key_exists($key, $settings) && filled($settings[$key])) {
                $method[$field === 'account_type' ? 'merchant_account_type' : $field] = $settings[$key];
            }
        }

        return $method;
    }

    /**
     * @return array<string, mixed>
     */
    private function settingValues(): array
    {
        try {
            return StoreSetting::query()
                ->where('setting_group', 'checkout')
                ->get(['key', 'value'])
                ->mapWithKeys(fn (StoreSetting $setting): array => [$setting->key => $setting->value['value'] ?? null])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $method
     * @return array<int, string>
     */
    private function readinessIssues(string $value, array $method): array
    {
        if (! (bool) ($method['enabled'] ?? false)) {
            return ['disabled'];
        }

        if (($method['instruction_type'] ?? null) === 'manual_mobile_payment' && blank($method['merchant_number'] ?? null)) {
            return ['merchant_number'];
        }

        if (in_array($value, [PaymentMethod::SslCommerz->value, PaymentMethod::Card->value], true) && ! $this->sslCommerzConfigured()) {
            return ['gateway_credentials'];
        }

        return [];
    }

    private function sslCommerzConfigured(): bool
    {
        return filled(config('payment_gateways.sslcommerz.store_id'))
            && filled(config('payment_gateways.sslcommerz.store_password'))
            && filled(config('payment_gateways.sslcommerz.init_url'))
            && filled(config('payment_gateways.sslcommerz.validation_url'));
    }

    private function nextAction(string $value, array $method): string
    {
        if ($value === PaymentMethod::CashOnDelivery->value) {
            return 'confirm_order';
        }

        if (($method['instruction_type'] ?? null) === 'manual_mobile_payment') {
            return 'manual_payment_instructions';
        }

        return 'gateway_pending';
    }

    /**
     * @return array<string, mixed>
     */
    private function normalize(string $value, array $method): array
    {
        return [
            'value' => $value,
            'enabled' => (bool) ($method['enabled'] ?? false),
            'gateway' => $method['gateway'] ?? null,
            'sort' => (int) ($method['sort'] ?? 100),
            'label' => (string) ($method['label'] ?? $value),
            'label_bn' => (string) ($method['label_bn'] ?? ($method['label'] ?? $value)),
            'short_label' => (string) ($method['short_label'] ?? ($method['label'] ?? $value)),
            'short_label_bn' => (string) ($method['short_label_bn'] ?? ($method['label_bn'] ?? ($method['label'] ?? $value))),
            'description' => (string) ($method['description'] ?? ''),
            'description_bn' => (string) ($method['description_bn'] ?? ($method['description'] ?? '')),
            'instruction_type' => $method['instruction_type'] ?? null,
            'merchant_number' => $method['merchant_number'] ?? null,
            'merchant_account_type' => $method['merchant_account_type'] ?? null,
            'next_action' => $this->nextAction($value, $method),
            'checkout_ready' => (bool) ($method['enabled'] ?? false) && $this->readinessIssues($value, $method) === [],
        ];
    }
}
