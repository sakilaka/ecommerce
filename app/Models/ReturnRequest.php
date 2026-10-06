<?php

namespace App\Models;

use App\Enums\ReturnStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['order_id', 'order_item_id', 'user_id', 'return_number', 'status', 'refund_amount', 'reason', 'admin_note', 'approved_at', 'rejected_at', 'refunded_at'])]
class ReturnRequest extends Model
{
    use HasFactory;

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pickupShipment(): HasOne
    {
        return $this->hasOne(Shipment::class)->where('direction', 'return_pickup');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReturnStatus::class,
            'refund_amount' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }
}
