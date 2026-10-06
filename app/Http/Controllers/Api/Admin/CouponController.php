<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\DiscountType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminCouponResource;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'running', 'expired'])],
            'type' => ['nullable', Rule::enum(DiscountType::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 12);

        $coupons = Coupon::query()
            ->withCount('orders')
            ->withSum('orders', 'discount_total')
            ->when(isset($validated['q']), fn (Builder $query): Builder => $query->where('code', 'like', '%'.$validated['q'].'%'))
            ->when(isset($validated['type']), fn (Builder $query): Builder => $query->where('type', $validated['type']))
            ->when(($validated['status'] ?? null) === 'active', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when(($validated['status'] ?? null) === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->when(($validated['status'] ?? null) === 'running', function (Builder $query): void {
                $query->where('is_active', true)
                    ->where(fn (Builder $query): Builder => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                    ->where(fn (Builder $query): Builder => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                    ->where(fn (Builder $query): Builder => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
            })
            ->when(($validated['status'] ?? null) === 'expired', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('ends_at', '<', now())
                        ->orWhereColumn('used_count', '>=', 'usage_limit');
                });
            })
            ->latest('updated_at')
            ->paginate($perPage);

        return AdminCouponResource::collection($coupons);
    }

    public function store(Request $request): AdminCouponResource
    {
        $coupon = Coupon::query()->create($this->payload($this->validatedData($request)));

        return AdminCouponResource::make($this->freshCoupon($coupon));
    }

    public function update(Request $request, Coupon $coupon): AdminCouponResource
    {
        $coupon->update($this->payload($this->validatedData($request, $coupon), $coupon));

        return AdminCouponResource::make($this->freshCoupon($coupon));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Coupon $coupon = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('coupons', 'code')->ignore($coupon)],
            'type' => ['required', Rule::enum(DiscountType::class)],
            'value' => ['required', 'integer', 'min:1', 'max:10000000'],
            'minimum_order_amount' => ['required', 'integer', 'min:0'],
            'maximum_discount_amount' => ['nullable', 'integer', 'min:1'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'used_count' => ['nullable', 'integer', 'min:0'],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['required', 'boolean'],
        ]);

        if (($validated['type'] ?? null) === DiscountType::Percent->value && (int) $validated['value'] > 90) {
            throw ValidationException::withMessages([
                'value' => 'Percent discounts may not exceed 90%.',
            ]);
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, ?Coupon $coupon = null): array
    {
        return [
            'code' => Str::upper($validated['code']),
            'type' => $validated['type'],
            'value' => $validated['value'],
            'minimum_order_amount' => $validated['minimum_order_amount'],
            'maximum_discount_amount' => $validated['maximum_discount_amount'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'used_count' => $validated['used_count'] ?? $coupon?->used_count ?? 0,
            'per_customer_limit' => $validated['per_customer_limit'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $validated['is_active'],
        ];
    }

    private function freshCoupon(Coupon $coupon): Coupon
    {
        return $coupon->refresh()
            ->loadCount('orders')
            ->loadSum('orders', 'discount_total');
    }
}
