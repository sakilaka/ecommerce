<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductReviewResource extends JsonResource
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
            'status' => $this->status?->value ?? $this->status,
            'is_verified_purchase' => $this->is_verified_purchase,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'admin_note' => $this->admin_note,
            'helpful_count' => $this->helpful_count,
            'moderated_at' => $this->moderated_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'product' => $this->whenLoaded('product', fn (): ?array => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'sku' => $this->product->sku,
            ] : null),
            'customer' => $this->whenLoaded('user', fn (): ?array => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ] : null),
            'order_item' => $this->whenLoaded('orderItem', fn (): ?array => $this->orderItem ? [
                'id' => $this->orderItem->id,
                'order_id' => $this->orderItem->order_id,
                'product_name' => $this->orderItem->product_name,
                'variant_name' => $this->orderItem->variant_name,
                'sku' => $this->orderItem->sku,
            ] : null),
            'moderator' => $this->whenLoaded('moderatedBy', fn (): ?array => $this->moderatedBy ? [
                'id' => $this->moderatedBy->id,
                'name' => $this->moderatedBy->name,
                'email' => $this->moderatedBy->email,
            ] : null),
        ];
    }
}
