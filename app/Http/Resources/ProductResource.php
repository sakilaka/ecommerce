<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $primaryImage = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_primary', true) ?? $this->images->first()
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'seo' => [
                'title' => $this->seo_title ?: $this->name,
                'description' => $this->seo_description ?: $this->short_description,
            ],
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'discount_percent' => $this->discount_percent,
            'is_featured' => $this->is_featured,
            'is_refundable' => $this->is_refundable,
            'return_window_days' => $this->return_window_days,
            'published_at' => $this->published_at?->toISOString(),
            'badge' => $this->meta['badge'] ?? null,
            'rating' => $this->meta['rating'] ?? null,
            'reviews' => $this->meta['reviews'] ?? null,
            'sold' => $this->meta['sold'] ?? null,
            'image_position' => $this->meta['image_position'] ?? null,
            'primary_image' => $primaryImage ? [
                'path' => $primaryImage->path,
                'alt_text' => $primaryImage->alt_text,
            ] : null,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'brand' => BrandResource::make($this->whenLoaded('brand')),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($variant): array => [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'options' => $variant->options,
                'price' => $variant->price,
                'compare_at_price' => $variant->compare_at_price,
                'stock_quantity' => $variant->stock_quantity,
                'oversell_allowed' => $variant->oversell_allowed,
                'is_active' => $variant->is_active,
            ])),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($image): array => [
                'id' => $image->id,
                'path' => $image->path,
                'alt_text' => $image->alt_text,
                'sort_order' => $image->sort_order,
                'is_primary' => $image->is_primary,
            ])),
        ];
    }
}
