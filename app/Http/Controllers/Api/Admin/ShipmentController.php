<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminShipmentResource;
use App\Models\AuditLog;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ShipmentStatus::class)],
            'courier' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:100'],
            'cod_only' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $shipments = Shipment::query()
            ->with(['events.user:id,name,email', 'order' => fn ($query) => $query->select('id', 'order_number', 'status', 'payment_status', 'payment_method', 'customer_name', 'customer_phone', 'grand_total')->withCount('items')])
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->where('status', $validated['status']))
            ->when(isset($validated['courier']), fn (Builder $query): Builder => $query->where('courier_name', $validated['courier']))
            ->when(isset($validated['district']), fn (Builder $query): Builder => $query->where('recipient_district', $validated['district']))
            ->when($request->boolean('cod_only'), fn (Builder $query): Builder => $query->where('cod_amount', '>', 0))
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('recipient_name', 'like', "%{$search}%")
                        ->orWhere('recipient_phone', 'like', "%{$search}%")
                        ->orWhere('tracking_number', 'like', "%{$search}%")
                        ->orWhereHas('order', fn (Builder $query): Builder => $query->where('order_number', 'like', "%{$search}%"));
                });
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return AdminShipmentResource::collection($shipments);
    }

    public function show(Shipment $shipment): AdminShipmentResource
    {
        return AdminShipmentResource::make($this->freshShipment($shipment));
    }

    public function update(Request $request, Shipment $shipment): AdminShipmentResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ShipmentStatus::class)],
            'courier_name' => ['nullable', 'string', 'max:80'],
            'tracking_number' => ['nullable', 'string', 'max:120'],
            'tracking_url' => ['nullable', 'url', 'max:255'],
            'estimated_delivery_at' => ['nullable', 'date'],
            'admin_note' => [Rule::requiredIf(fn (): bool => $request->input('status') === ShipmentStatus::Failed->value), 'nullable', 'string', 'max:1000'],
            'event_location' => ['nullable', 'string', 'max:100'],
        ]);

        $before = $shipment->only(['status', 'courier_name', 'tracking_number', 'tracking_url', 'estimated_delivery_at', 'shipped_at', 'delivered_at', 'failed_at', 'admin_note']);
        $status = $validated['status'];

        $shipment->update([
            'status' => $status,
            'courier_name' => $validated['courier_name'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'tracking_url' => $validated['tracking_url'] ?? null,
            'estimated_delivery_at' => $validated['estimated_delivery_at'] ?? $shipment->estimated_delivery_at,
            'shipped_at' => in_array($status, [ShipmentStatus::Shipped->value, ShipmentStatus::InTransit->value, ShipmentStatus::Delivered->value], true) ? ($shipment->shipped_at ?? now()) : $shipment->shipped_at,
            'delivered_at' => $status === ShipmentStatus::Delivered->value ? ($shipment->delivered_at ?? now()) : $shipment->delivered_at,
            'failed_at' => $status === ShipmentStatus::Failed->value ? ($shipment->failed_at ?? now()) : $shipment->failed_at,
            'admin_note' => $validated['admin_note'] ?? null,
        ]);

        $shipment->refresh();
        $this->syncOrderDeliveryState($shipment);
        $shipment->events()->create([
            'user_id' => $request->user()?->id,
            'status' => $status,
            'event_type' => $status === ShipmentStatus::Failed->value ? 'delivery_failed' : 'status_update',
            'location' => $validated['event_location'] ?? $shipment->recipient_district,
            'message' => $validated['admin_note'] ?? $this->defaultEventMessage($status),
            'is_public' => true,
            'metadata' => [
                'previous_status' => $before['status'] instanceof ShipmentStatus ? $before['status']->value : $before['status'],
                'courier_name' => $shipment->courier_name,
                'tracking_number' => $shipment->tracking_number,
            ],
            'occurred_at' => now(),
        ]);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'shipment.updated',
            'auditable_type' => Shipment::class,
            'auditable_id' => $shipment->id,
            'old_values' => $before,
            'new_values' => $shipment->only(['status', 'courier_name', 'tracking_number', 'tracking_url', 'estimated_delivery_at', 'shipped_at', 'delivered_at', 'failed_at', 'admin_note']),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return AdminShipmentResource::make($this->freshShipment($shipment));
    }

    private function syncOrderDeliveryState(Shipment $shipment): void
    {
        $order = $shipment->order;

        if (! $order) {
            return;
        }

        if ($shipment->status === ShipmentStatus::Delivered) {
            $order->update([
                'status' => OrderStatus::Delivered,
                'confirmed_at' => $order->confirmed_at ?? now(),
                'shipped_at' => $order->shipped_at ?? ($shipment->shipped_at ?? now()),
                'delivered_at' => $order->delivered_at ?? now(),
            ]);

            return;
        }

        if (in_array($shipment->status, [ShipmentStatus::Shipped, ShipmentStatus::InTransit], true)) {
            $order->update([
                'status' => OrderStatus::Shipped,
                'confirmed_at' => $order->confirmed_at ?? now(),
                'shipped_at' => $order->shipped_at ?? now(),
            ]);
        }
    }

    private function freshShipment(Shipment $shipment): Shipment
    {
        return $shipment->refresh()
            ->load([
                'events.user:id,name,email',
                'order' => fn ($query) => $query->select('id', 'order_number', 'status', 'payment_status', 'payment_method', 'customer_name', 'customer_phone', 'grand_total')->withCount('items'),
            ]);
    }

    private function defaultEventMessage(string $status): string
    {
        return match ($status) {
            ShipmentStatus::ReadyToShip->value => 'Shipment is packed and ready for courier pickup.',
            ShipmentStatus::Shipped->value => 'Shipment has been handed over to courier.',
            ShipmentStatus::InTransit->value => 'Shipment is moving through the courier network.',
            ShipmentStatus::Delivered->value => 'Shipment has been delivered to the customer.',
            ShipmentStatus::Returned->value => 'Shipment has been marked as returned.',
            ShipmentStatus::Cancelled->value => 'Shipment has been cancelled.',
            default => 'Shipment status was updated.',
        };
    }
}
