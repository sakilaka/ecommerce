<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class StoreSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = $this->settings();

        return response()->json([
            'data' => $settings,
            'payment_readiness' => $this->paymentReadiness($settings),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'identity' => ['required', 'array:store_name,tagline,support_phone,support_email,address'],
            'checkout' => ['required', 'array:cod_enabled,sslcommerz_enabled,bkash_enabled,nagad_enabled,card_enabled,bkash_merchant_number,bkash_account_type,nagad_merchant_number,nagad_account_type'],
            'delivery' => ['required', 'array:free_delivery_threshold,inside_dhaka_charge,outside_dhaka_charge,cod_charge'],
            'returns' => ['required', 'array:return_window_days,refund_support_enabled'],
            'operations' => ['required', 'array:overselling_allowed,auto_confirm_cod_orders,low_stock_threshold'],
            'identity.store_name' => ['required', 'string', 'max:80'],
            'identity.tagline' => ['nullable', 'string', 'max:120'],
            'identity.support_phone' => ['required', 'string', 'max:30'],
            'identity.support_email' => ['required', 'email', 'max:120'],
            'identity.address' => ['nullable', 'string', 'max:180'],
            'checkout.cod_enabled' => ['required', 'boolean'],
            'checkout.sslcommerz_enabled' => ['required', 'boolean'],
            'checkout.bkash_enabled' => ['required', 'boolean'],
            'checkout.nagad_enabled' => ['required', 'boolean'],
            'checkout.card_enabled' => ['required', 'boolean'],
            'checkout.bkash_merchant_number' => ['nullable', 'string', 'max:30'],
            'checkout.bkash_account_type' => ['nullable', 'string', 'max:30'],
            'checkout.nagad_merchant_number' => ['nullable', 'string', 'max:30'],
            'checkout.nagad_account_type' => ['nullable', 'string', 'max:30'],
            'delivery.free_delivery_threshold' => ['required', 'integer', 'min:0', 'max:100000000'],
            'delivery.inside_dhaka_charge' => ['required', 'integer', 'min:0', 'max:10000000'],
            'delivery.outside_dhaka_charge' => ['required', 'integer', 'min:0', 'max:10000000'],
            'delivery.cod_charge' => ['required', 'integer', 'min:0', 'max:10000000'],
            'returns.return_window_days' => ['required', 'integer', 'min:0', 'max:60'],
            'returns.refund_support_enabled' => ['required', 'boolean'],
            'operations.overselling_allowed' => ['required', 'boolean'],
            'operations.auto_confirm_cod_orders' => ['required', 'boolean'],
            'operations.low_stock_threshold' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $allowedKeys = $this->defaultSettings();
        $before = $this->settings();

        DB::transaction(function () use ($allowedKeys, $request, $validated, $before): void {
            foreach ($allowedKeys as $group => $settings) {
                foreach (array_keys($settings) as $key) {
                    StoreSetting::query()->updateOrCreate(
                        ['key' => $group.'.'.$key],
                        [
                            'setting_group' => $group,
                            'value' => ['value' => Arr::get($validated, $group.'.'.$key)],
                        ],
                    );
                }
            }

            AuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'event' => 'store_settings.updated',
                'auditable_type' => StoreSetting::class,
                'auditable_id' => null,
                'old_values' => $before,
                'new_values' => $validated,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        $settings = $this->settings();

        return response()->json([
            'message' => 'Store settings updated.',
            'data' => $settings,
            'payment_readiness' => $this->paymentReadiness($settings),
        ]);
    }

    /**
     * @param  array<string, array<string, mixed>>  $settings
     * @return array<string, mixed>
     */
    private function paymentReadiness(array $settings): array
    {
        $checkout = $settings['checkout'] ?? [];
        $sslCommerzMissing = $this->missingSslCommerzConfig();
        $methods = [
            'cod' => $this->readinessItem(
                'Cash on Delivery',
                'ক্যাশ অন ডেলিভারি',
                (bool) ($checkout['cod_enabled'] ?? false),
                []
            ),
            'bkash' => $this->readinessItem(
                'bKash Manual Payment',
                'বিকাশ ম্যানুয়াল পেমেন্ট',
                (bool) ($checkout['bkash_enabled'] ?? false),
                filled($checkout['bkash_merchant_number'] ?? null) ? [] : ['bKash merchant number']
            ),
            'nagad' => $this->readinessItem(
                'Nagad Manual Payment',
                'নগদ ম্যানুয়াল পেমেন্ট',
                (bool) ($checkout['nagad_enabled'] ?? false),
                filled($checkout['nagad_merchant_number'] ?? null) ? [] : ['Nagad merchant number']
            ),
            'sslcommerz' => $this->readinessItem(
                'SSLCommerz Gateway',
                'SSLCommerz গেটওয়ে',
                (bool) ($checkout['sslcommerz_enabled'] ?? false),
                $sslCommerzMissing
            ),
            'card' => $this->readinessItem(
                'Card Payment',
                'কার্ড পেমেন্ট',
                (bool) ($checkout['card_enabled'] ?? false),
                $sslCommerzMissing === [] ? [] : array_merge(['SSLCommerz card rail'], $sslCommerzMissing)
            ),
        ];

        $enabled = collect($methods)->filter(fn (array $method): bool => $method['enabled']);

        return [
            'overall_status' => $enabled->contains(fn (array $method): bool => $method['status'] === 'needs_setup') ? 'needs_setup' : 'ready',
            'ready_count' => $enabled->where('status', 'ready')->count(),
            'enabled_count' => $enabled->count(),
            'sandbox' => (bool) config('payment_gateways.sslcommerz.sandbox', true),
            'methods' => $methods,
        ];
    }

    /**
     * @param  array<int, string>  $missing
     * @return array<string, mixed>
     */
    private function readinessItem(string $label, string $labelBn, bool $enabled, array $missing): array
    {
        return [
            'label' => $label,
            'label_bn' => $labelBn,
            'enabled' => $enabled,
            'status' => ! $enabled ? 'disabled' : ($missing === [] ? 'ready' : 'needs_setup'),
            'missing' => $enabled ? $missing : [],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function missingSslCommerzConfig(): array
    {
        return collect([
            'store ID' => config('payment_gateways.sslcommerz.store_id'),
            'store password' => config('payment_gateways.sslcommerz.store_password'),
            'init URL' => config('payment_gateways.sslcommerz.init_url'),
            'validation URL' => config('payment_gateways.sslcommerz.validation_url'),
            'success callback URL' => config('payment_gateways.sslcommerz.success_url'),
            'fail callback URL' => config('payment_gateways.sslcommerz.fail_url'),
            'cancel callback URL' => config('payment_gateways.sslcommerz.cancel_url'),
            'IPN callback URL' => config('payment_gateways.sslcommerz.ipn_url'),
        ])
            ->filter(fn (mixed $value): bool => blank($value))
            ->keys()
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function settings(): array
    {
        $settings = $this->defaultSettings();

        StoreSetting::query()
            ->whereIn('key', $this->settingKeys())
            ->get(['key', 'value'])
            ->each(function (StoreSetting $setting) use (&$settings): void {
                [$group, $name] = explode('.', $setting->key, 2);

                if (array_key_exists($group, $settings) && array_key_exists($name, $settings[$group])) {
                    $settings[$group][$name] = $setting->value['value'] ?? null;
                }
            });

        return $settings;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function defaultSettings(): array
    {
        return [
            'identity' => [
                'store_name' => 'Aurelia',
                'tagline' => 'Premium Bangladesh Market',
                'support_phone' => '09612-345678',
                'support_email' => 'support@aurelia.test',
                'address' => 'Dhaka, Bangladesh',
            ],
            'checkout' => [
                'cod_enabled' => true,
                'sslcommerz_enabled' => false,
                'bkash_enabled' => false,
                'nagad_enabled' => false,
                'card_enabled' => false,
                'bkash_merchant_number' => config('payment_methods.methods.bkash.merchant_number'),
                'bkash_account_type' => config('payment_methods.methods.bkash.merchant_account_type'),
                'nagad_merchant_number' => config('payment_methods.methods.nagad.merchant_number'),
                'nagad_account_type' => config('payment_methods.methods.nagad.merchant_account_type'),
            ],
            'delivery' => [
                'free_delivery_threshold' => 199900,
                'inside_dhaka_charge' => 7000,
                'outside_dhaka_charge' => 12000,
                'cod_charge' => 0,
            ],
            'returns' => [
                'return_window_days' => 7,
                'refund_support_enabled' => true,
            ],
            'operations' => [
                'overselling_allowed' => true,
                'auto_confirm_cod_orders' => false,
                'low_stock_threshold' => 5,
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function settingKeys(): array
    {
        return collect($this->defaultSettings())
            ->flatMap(fn (array $settings, string $group): array => array_map(fn (string $key): string => $group.'.'.$key, array_keys($settings)))
            ->values()
            ->all();
    }
}
