<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminCustomerResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $customers = User::query()
            ->where('role', UserRole::Customer->value)
            ->withCount([
                'orders',
                'addresses',
                'orders as delivered_orders_count' => fn (Builder $query): Builder => $query->where('status', OrderStatus::Delivered->value),
            ])
            ->withSum(['orders as paid_orders_sum_grand_total' => fn (Builder $query): Builder => $query->where('payment_status', PaymentStatus::Paid->value)], 'grand_total')
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('active', $validated), fn (Builder $query): Builder => $query->where('is_active', $request->boolean('active')))
            ->latest('created_at')
            ->paginate($perPage);

        return AdminCustomerResource::collection($customers);
    }

    public function show(User $customer): AdminCustomerResource
    {
        abort_unless($customer->role === UserRole::Customer, 404);

        $customer->load([
            'addresses' => fn ($query) => $query->orderByDesc('is_default')->latest('updated_at'),
            'orders' => fn ($query) => $query->select(['id', 'user_id', 'order_number', 'status', 'payment_status', 'payment_method', 'shipping_district', 'shipping_area', 'grand_total', 'created_at'])->latest()->limit(8),
        ])
            ->loadCount([
                'orders',
                'addresses',
                'orders as delivered_orders_count' => fn (Builder $query): Builder => $query->where('status', OrderStatus::Delivered->value),
            ])
            ->loadSum(['orders as paid_orders_sum_grand_total' => fn (Builder $query): Builder => $query->where('payment_status', PaymentStatus::Paid->value)], 'grand_total');

        return AdminCustomerResource::make($customer);
    }

    public function update(Request $request, User $customer): AdminCustomerResource
    {
        abort_unless($customer->role === UserRole::Customer, 404);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $customer->update(['is_active' => $validated['is_active']]);

        return AdminCustomerResource::make($this->freshCustomer($customer));
    }

    private function freshCustomer(User $customer): User
    {
        return $customer->refresh()
            ->loadCount([
                'orders',
                'addresses',
                'orders as delivered_orders_count' => fn (Builder $query): Builder => $query->where('status', OrderStatus::Delivered->value),
            ])
            ->loadSum(['orders as paid_orders_sum_grand_total' => fn (Builder $query): Builder => $query->where('payment_status', PaymentStatus::Paid->value)], 'grand_total');
    }
}
