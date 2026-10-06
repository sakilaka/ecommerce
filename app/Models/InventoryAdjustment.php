<?php

namespace App\Models;

use App\Enums\InventoryAdjustmentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_variant_id', 'user_id', 'type', 'quantity_delta', 'stock_after', 'reference_type', 'reference_id', 'note'])]
class InventoryAdjustment extends Model
{
    use HasFactory;

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InventoryAdjustmentType::class,
            'quantity_delta' => 'integer',
            'stock_after' => 'integer',
        ];
    }
}
