<?php

namespace App\Http\Resources\Storefront;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomepageSectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type?->value ?? $this->type,
            'title' => $this->title,
            'sort_order' => $this->sort_order,
            'content' => $this->content,
            'settings' => $this->settings,
            'coupon' => $this->couponPayload($this->resource->getAttribute('promotion_coupon')),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function couponPayload(?Coupon $coupon): ?array
    {
        if (! $coupon) {
            return null;
        }

        $usageLimit = $coupon->usage_limit;

        return [
            'code' => $coupon->code,
            'type' => $coupon->type?->value ?? $coupon->type,
            'value' => $coupon->value,
            'minimum_order_amount' => $coupon->minimum_order_amount,
            'maximum_discount_amount' => $coupon->maximum_discount_amount,
            'remaining_uses' => $usageLimit === null ? null : max(0, (int) $usageLimit - (int) $coupon->used_count),
            'ends_at' => $coupon->ends_at?->toISOString(),
        ];
    }
}
