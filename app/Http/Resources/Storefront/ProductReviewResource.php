<?php

namespace App\Http\Resources\Storefront;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'body' => $this->body,
            'customer_name' => $this->customer_name ?: $this->user?->name,
            'is_verified_purchase' => $this->is_verified_purchase,
            'helpful_count' => $this->helpful_count,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
