<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'status' => $this->status?->value ?? $this->status,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'cost_price' => $this->cost_price,
            'discount_percent' => $this->discount_percent,
            'sale_starts_at' => $this->sale_starts_at?->toISOString(),
            'sale_ends_at' => $this->sale_ends_at?->toISOString(),
            'is_featured' => $this->is_featured,
            'is_refundable' => $this->is_refundable,
            'return_window_days' => $this->return_window_days,
            'weight_kg' => $this->weight_kg,
            'published_at' => $this->published_at?->toISOString(),
            'category' => $this->whenLoaded('category', fn (): ?array => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'brand' => $this->whenLoaded('brand', fn (): ?array => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
                'is_official' => $this->brand->is_official,
            ] : null),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($variant): array => [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'compare_at_price' => $variant->compare_at_price,
                'stock_quantity' => $variant->stock_quantity,
                'reserved_quantity' => $variant->reserved_quantity,
                'low_stock_threshold' => $variant->low_stock_threshold,
                'oversell_allowed' => $variant->oversell_allowed,
                'is_active' => $variant->is_active,
            ])->values()),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image): array => [
                'id' => $image->id,
                'path' => $image->path,
                'alt_text' => $image->alt_text,
                'sort_order' => $image->sort_order,
                'is_primary' => $image->is_primary,
            ])->values()),
            'variants_count' => $this->variants_count,
            'stock_quantity' => (int) ($this->variants_sum_stock_quantity ?? 0),
            'reserved_quantity' => (int) ($this->variants_sum_reserved_quantity ?? 0),
            'low_stock_variants' => $this->low_stock_variants_count,
        ];
    }
}
