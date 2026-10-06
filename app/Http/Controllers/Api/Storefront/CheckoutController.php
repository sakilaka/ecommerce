<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\CartStatus;
use App\Enums\InventoryAdjustmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\StoreCheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\CustomerAddress;
use App\Models\DeliveryZone;
use App\Models\InventoryAdjustment;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\CouponQuoteService;
use App\Services\PaymentMethodCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function store(StoreCheckoutRequest $request, CouponQuoteService $coupons, PaymentMethodCatalog $paymentMethods): OrderResource|JsonResponse
    {
        $validated = $request->validated();
        $cart = $this->findActiveCart($request->user()?->id, $validated['cart_session_id'] ?? $request->header('X-Cart-Session'));

        if (! $cart || $cart->items()->count() === 0) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        $paymentMethod = PaymentMethod::from($validated['payment_method']);
        $paymentDefinition = $paymentMethods->find($paymentMethod);

        if (! $paymentMethods->isCheckoutAvailable($paymentMethod)) {
            return response()->json(['message' => ($paymentDefinition['label'] ?? $paymentMethod->value).' is not available for checkout yet.'], 422);
        }

        if (! $paymentMethods->isCheckoutReady($paymentMethod)) {
            return response()->json(['message' => ($paymentDefinition['label'] ?? $paymentMethod->value).' is not ready for checkout yet.'], 422);
        }

        $cart->load(['items.product', 'items.productVariant']);
        $savedAddress = $this->resolveCustomerAddress($request->user()?->id, $validated['customer_address_id'] ?? null);
        $shipping = $savedAddress ? $this->shippingFromAddress($savedAddress) : $validated['shipping'];
        $deliveryZone = $this->findDeliveryZone($shipping['district'], $shipping['area'] ?? null);

        if (! $deliveryZone) {
            return response()->json(['message' => 'Delivery is not available for this district or area yet.'], 422);
        }

        if ($paymentMethod === PaymentMethod::CashOnDelivery && ! $deliveryZone->cod_available) {
            return response()->json(['message' => 'Cash on delivery is not available for this district or area.'], 422);
        }

        $order = DB::transaction(function () use ($cart, $validated, $deliveryZone, $savedAddress, $shipping, $request, $coupons, $paymentMethod, $paymentMethods, $paymentDefinition): Order {
            $cart->refresh()->load(['items.product', 'items.productVariant']);
            $subtotal = $cart->items->sum(fn ($item): int => $item->unit_price * $item->quantity);
            $coupon = isset($validated['coupon_code']) ? $coupons->resolve($validated['coupon_code'], $subtotal, $request->user()?->id) : null;

            if (isset($validated['coupon_code']) && ! $coupon) {
                abort(response()->json(['message' => 'Coupon is not valid for this cart.'], 422));
            }

            $discount = $coupon ? $coupons->discount($coupon, $subtotal) : 0;
            $paymentStatus = $paymentMethods->initialStatus($paymentMethod);
            $shippingTotal = $deliveryZone->delivery_charge + ($paymentMethod === PaymentMethod::CashOnDelivery ? $deliveryZone->cod_charge : 0);
            $grandTotal = max(0, $subtotal + $shippingTotal - $discount);

            $order = Order::query()->create([
                'user_id' => $cart->user_id,
                'customer_address_id' => $savedAddress?->id,
                'coupon_id' => $coupon?->id,
                'order_number' => $this->nextOrderNumber(),
                'status' => OrderStatus::Pending,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'customer_name' => $validated['customer']['name'],
                'customer_email' => $validated['customer']['email'] ?? null,
                'customer_phone' => $validated['customer']['phone'],
                'shipping_address' => $shipping,
                'shipping_district' => $shipping['district'],
                'shipping_area' => $shipping['area'] ?? null,
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'shipping_total' => $shippingTotal,
                'tax_total' => 0,
                'grand_total' => $grandTotal,
                'customer_note' => $validated['customer_note'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                $variant = ProductVariant::query()->lockForUpdate()->find($item->product_variant_id);

                if ($variant && ! $this->hasAvailableStock($variant, $item->quantity)) {
                    abort(response()->json(['message' => 'Requested quantity is not available.'], 422));
                }

                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product->name,
                    'variant_name' => $item->productVariant?->name,
                    'sku' => $item->productVariant?->sku ?? $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->unit_price * $item->quantity,
                    'is_refundable' => $item->product->is_refundable,
                    'return_window_days' => $item->product->return_window_days,
                    'product_snapshot' => [
                        'name' => $item->product->name,
                        'slug' => $item->product->slug,
                        'sku' => $item->product->sku,
                        'price_snapshot' => $item->price_snapshot,
                    ],
                ]);

                if ($variant) {
                    $variant->decrement('stock_quantity', $item->quantity);
                    $variant->refresh();

                    InventoryAdjustment::query()->create([
                        'product_variant_id' => $variant->id,
                        'user_id' => $cart->user_id,
                        'type' => InventoryAdjustmentType::Sale,
                        'quantity_delta' => -$item->quantity,
                        'stock_after' => $variant->stock_quantity,
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'note' => 'Order placement stock deduction.',
                    ]);
                }
            }

            $order->payments()->create([
                'method' => $paymentMethod,
                'status' => $paymentStatus,
                'amount' => $grandTotal,
                'gateway' => $paymentDefinition['gateway'] ?? null,
                'gateway_transaction_id' => null,
                'gateway_payload' => $this->gatewayPayload($paymentMethod, $paymentDefinition, $order, $grandTotal),
                'paid_at' => null,
            ]);

            $shipment = $order->shipments()->create([
                'status' => ShipmentStatus::Pending,
                'delivery_charge' => $deliveryZone->delivery_charge,
                'cod_charge' => $paymentMethod === PaymentMethod::CashOnDelivery ? $deliveryZone->cod_charge : 0,
                'cod_amount' => $paymentMethod === PaymentMethod::CashOnDelivery ? $grandTotal : 0,
                'recipient_name' => $validated['customer']['name'],
                'recipient_phone' => $validated['customer']['phone'],
                'recipient_district' => $shipping['district'],
                'recipient_area' => $shipping['area'] ?? null,
                'recipient_address' => $shipping['address_line'],
                'estimated_delivery_at' => now()->addDays($deliveryZone->estimated_max_days),
                'metadata' => [
                    'delivery_zone_id' => $deliveryZone->id,
                    'estimated_min_days' => $deliveryZone->estimated_min_days,
                    'estimated_max_days' => $deliveryZone->estimated_max_days,
                ],
            ]);

            $shipment->events()->create([
                'user_id' => $cart->user_id,
                'status' => ShipmentStatus::Pending,
                'event_type' => 'order_placed',
                'location' => $shipping['district'],
                'message' => 'Order received and delivery preparation started.',
                'is_public' => true,
                'metadata' => [
                    'order_number' => $order->order_number,
                    'delivery_zone_id' => $deliveryZone->id,
                ],
                'occurred_at' => now(),
            ]);

            if ($coupon) {
                $coupon->increment('used_count');
            }

            $cart->update(['status' => CartStatus::Ordered]);

            return $order;
        });

        return OrderResource::make($order->load(['items', 'payments', 'shipments.events']));
    }

    /**
     * @param  array<string, mixed>  $paymentDefinition
     * @return array<string, mixed>|null
     */
    private function gatewayPayload(PaymentMethod $paymentMethod, array $paymentDefinition, Order $order, int $grandTotal): ?array
    {
        if ($paymentMethod === PaymentMethod::CashOnDelivery) {
            return null;
        }

        if (($paymentDefinition['instruction_type'] ?? null) === 'manual_mobile_payment') {
            return [
                'next_action' => 'manual_payment_instructions',
                'instruction_type' => 'manual_mobile_payment',
                'merchant_number' => $paymentDefinition['merchant_number'] ?? null,
                'merchant_account_type' => $paymentDefinition['merchant_account_type'] ?? null,
                'reference' => $order->order_number,
                'amount' => $grandTotal,
                'message' => 'Send money to the merchant number and keep the transaction ID for admin verification.',
                'message_bn' => 'Merchant নম্বরে Send Money করে transaction ID সংরক্ষণ করুন। Admin verify করার পর payment paid হবে।',
            ];
        }

        return [
            'next_action' => $paymentDefinition['next_action'] ?? 'gateway_pending',
            'message' => 'Gateway initiation will be connected in the next payment integration step.',
        ];
    }

    private function findActiveCart(?int $userId, ?string $sessionId): ?Cart
    {
        return Cart::query()
            ->where('status', CartStatus::Active)
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when(! $userId, fn ($query) => $query->where('session_id', $sessionId))
            ->first();
    }

    private function resolveCustomerAddress(?int $userId, ?int $addressId): ?CustomerAddress
    {
        if (! $userId || ! $addressId) {
            return null;
        }

        return CustomerAddress::query()
            ->where('user_id', $userId)
            ->find($addressId);
    }

    /**
     * @return array<string, mixed>
     */
    private function shippingFromAddress(CustomerAddress $address): array
    {
        return [
            'label' => $address->label,
            'district' => $address->district,
            'area' => $address->area,
            'postal_code' => $address->postal_code,
            'address_line' => $address->address_line,
        ];
    }

    private function findDeliveryZone(string $district, ?string $area): ?DeliveryZone
    {
        return DeliveryZone::query()
            ->where('district', $district)
            ->where('is_active', true)
            ->where(function ($query) use ($area): void {
                $query->whereNull('area');

                if ($area) {
                    $query->orWhere('area', $area);
                }
            })
            ->orderByRaw('area is null')
            ->first();
    }

    private function hasAvailableStock(ProductVariant $variant, int $quantity): bool
    {
        if ($variant->oversell_allowed) {
            return true;
        }

        return ($variant->stock_quantity - $variant->reserved_quantity) >= $quantity;
    }

    private function nextOrderNumber(): string
    {
        return 'ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
    }
}
