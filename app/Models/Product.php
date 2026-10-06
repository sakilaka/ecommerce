<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['category_id', 'brand_id', 'name', 'slug', 'sku', 'status', 'short_description', 'description', 'seo_title', 'seo_description', 'price', 'compare_at_price', 'cost_price', 'discount_percent', 'sale_starts_at', 'sale_ends_at', 'is_featured', 'is_refundable', 'return_window_days', 'weight_kg', 'meta', 'published_at'])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'cost_price' => 'integer',
            'discount_percent' => 'integer',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_refundable' => 'boolean',
            'return_window_days' => 'integer',
            'weight_kg' => 'decimal:3',
            'meta' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
