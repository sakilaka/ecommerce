<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $usageLimit = $this->usage_limit;
        $remainingUses = $usageLimit === null ? null : max(0, (int) $usageLimit - (int) $this->used_count);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type?->value ?? $this->type,
            'value' => $this->value,
            'minimum_order_amount' => $this->minimum_order_amount,
            'maximum_discount_amount' => $this->maximum_discount_amount,
            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,
            'remaining_uses' => $remainingUses,
            'per_customer_limit' => $this->per_customer_limit,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),
            'is_active' => $this->is_active,
            'is_running' => $this->is_active
                && ($this->starts_at === null || $this->starts_at->lte(now()))
                && ($this->ends_at === null || $this->ends_at->gte(now()))
                && ($usageLimit === null || (int) $this->used_count < (int) $usageLimit),
            'orders_count' => (int) ($this->orders_count ?? 0),
            'orders_sum_discount_total' => (int) ($this->orders_sum_discount_total ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
