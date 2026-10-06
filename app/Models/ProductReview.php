<?php

namespace App\Models;

use App\Enums\ProductReviewStatus;
use Database\Factories\ProductReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'user_id', 'order_item_id', 'moderated_by', 'rating', 'title', 'body', 'status', 'is_verified_purchase', 'customer_name', 'customer_email', 'customer_phone', 'admin_note', 'moderated_at', 'helpful_count'])]
class ProductReview extends Model
{
    /** @use HasFactory<ProductReviewFactory> */
    use HasFactory;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductReviewStatus::class,
            'rating' => 'integer',
            'is_verified_purchase' => 'boolean',
            'moderated_at' => 'datetime',
            'helpful_count' => 'integer',
        ];
    }
}
