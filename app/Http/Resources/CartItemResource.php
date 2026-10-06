<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->whenLoaded('product');
        $variant = $this->whenLoaded('productVariant');
        $primaryImage = $this->product?->relationLoaded('images')
            ? $this->product->images->firstWhere('is_primary', true) ?? $this->product->images->first()
            : null;

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'name' => $this->product?->name,
            'slug' => $this->product?->slug,
            'variant_name' => $this->productVariant?->name,
            'sku' => $this->productVariant?->sku ?? $this->product?->sku,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'line_total' => $this->unit_price * $this->quantity,
            'price_snapshot' => $this->price_snapshot,
            'product' => ProductResource::make($product),
            'variant' => $variant,
            'primary_image' => $primaryImage ? [
                'path' => $primaryImage->path,
                'alt_text' => $primaryImage->alt_text,
            ] : null,
        ];
    }
}
