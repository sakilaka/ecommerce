<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['order_id', 'return_request_id', 'direction', 'status', 'courier_name', 'tracking_number', 'tracking_url', 'delivery_charge', 'cod_charge', 'cod_amount', 'recipient_name', 'recipient_phone', 'recipient_district', 'recipient_area', 'recipient_address', 'estimated_delivery_at', 'shipped_at', 'delivered_at', 'failed_at', 'admin_note', 'metadata'])]
class Shipment extends Model
{
    use HasFactory;

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)->latest('occurred_at')->latest('id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'delivery_charge' => 'integer',
            'cod_charge' => 'integer',
            'cod_amount' => 'integer',
            'estimated_delivery_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
