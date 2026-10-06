<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminOrderResource;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'district' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $orders = Order::query()
            ->with('user:id,name,email,phone')
            ->withCount('items')
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->where('status', $validated['status']))
            ->when(isset($validated['payment_status']), fn (Builder $query): Builder => $query->where('payment_status', $validated['payment_status']))
            ->when(isset($validated['payment_method']), fn (Builder $query): Builder => $query->where('payment_method', $validated['payment_method']))
            ->when(isset($validated['district']), fn (Builder $query): Builder => $query->where('shipping_district', $validated['district']))
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return AdminOrderResource::collection($orders);
    }

    public function show(Order $order): AdminOrderResource
    {
        return AdminOrderResource::make($this->freshOrder($order));
    }

    public function update(Request $request, Order $order): AdminOrderResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
        ]);

        $status = $validated['status'];
        $order->update([
            'status' => $status,
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'],
            'confirmed_at' => in_array($status, [OrderStatus::Confirmed->value, OrderStatus::Processing->value, OrderStatus::Shipped->value, OrderStatus::Delivered->value], true) ? ($order->confirmed_at ?? now()) : $order->confirmed_at,
            'shipped_at' => in_array($status, [OrderStatus::Shipped->value, OrderStatus::Delivered->value], true) ? ($order->shipped_at ?? now()) : $order->shipped_at,
            'delivered_at' => $status === OrderStatus::Delivered->value ? ($order->delivered_at ?? now()) : $order->delivered_at,
            'cancelled_at' => $status === OrderStatus::Cancelled->value ? ($order->cancelled_at ?? now()) : $order->cancelled_at,
        ]);

        return AdminOrderResource::make($this->freshOrder($order));
    }

    private function freshOrder(Order $order): Order
    {
        return $order->refresh()
            ->load(['user:id,name,email,phone', 'items', 'shipments'])
            ->loadCount('items');
    }
}
