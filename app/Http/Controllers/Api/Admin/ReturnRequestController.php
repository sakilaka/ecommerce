<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ReturnStatus;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminReturnRequestResource;
use App\Models\AuditLog;
use App\Models\ReturnRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ReturnRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ReturnStatus::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 12);

        $returns = ReturnRequest::query()
            ->with([
                'user:id,name,email,phone',
                'order:id,order_number,status,payment_status,payment_method,customer_name,customer_phone,shipping_district,shipping_area,grand_total,created_at',
                'orderItem:id,order_id,product_name,variant_name,sku,quantity,unit_price,line_total,is_refundable,return_window_days',
                'pickupShipment.events',
            ])
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('return_number', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhereHas('order', fn (Builder $query): Builder => $query->where('order_number', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%")->orWhere('customer_phone', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->where('status', $validated['status']))
            ->latest('created_at')
            ->paginate($perPage);

        return AdminReturnRequestResource::collection($returns);
    }

    public function show(ReturnRequest $returnRequest): AdminReturnRequestResource
    {
        return AdminReturnRequestResource::make($this->loadReturn($returnRequest));
    }

    public function update(Request $request, ReturnRequest $returnRequest): AdminReturnRequestResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ReturnStatus::class)],
            'refund_amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'pickup_courier_name' => ['nullable', 'string', 'max:80'],
            'pickup_tracking_number' => ['nullable', 'string', 'max:120'],
            'pickup_tracking_url' => ['nullable', 'url', 'max:255'],
            'pickup_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = ReturnStatus::from($validated['status']);
        $before = $this->auditValues($returnRequest);

        $returnRequest->update([
            'status' => $status,
            'refund_amount' => $validated['refund_amount'],
            'admin_note' => $validated['admin_note'] ?? null,
            'approved_at' => $status === ReturnStatus::Approved && ! $returnRequest->approved_at ? now() : $returnRequest->approved_at,
            'rejected_at' => $status === ReturnStatus::Rejected && ! $returnRequest->rejected_at ? now() : $returnRequest->rejected_at,
            'refunded_at' => $status === ReturnStatus::Refunded && ! $returnRequest->refunded_at ? now() : $returnRequest->refunded_at,
        ]);

        $this->syncPickupShipment($returnRequest->refresh(), $validated, $request);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'return_request.updated',
            'auditable_type' => ReturnRequest::class,
            'auditable_id' => $returnRequest->id,
            'old_values' => $before,
            'new_values' => $this->auditValues($returnRequest),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return AdminReturnRequestResource::make($this->loadReturn($returnRequest->refresh()));
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(ReturnRequest $returnRequest): array
    {
        return [
            'status' => $returnRequest->status?->value ?? $returnRequest->status,
            'refund_amount' => $returnRequest->refund_amount,
            'admin_note' => $returnRequest->admin_note,
            'approved_at' => $returnRequest->approved_at?->toISOString(),
            'rejected_at' => $returnRequest->rejected_at?->toISOString(),
            'refunded_at' => $returnRequest->refunded_at?->toISOString(),
        ];
    }

    private function loadReturn(ReturnRequest $returnRequest): ReturnRequest
    {
        return $returnRequest->load([
            'user:id,name,email,phone',
            'order:id,order_number,status,payment_status,payment_method,customer_name,customer_phone,shipping_district,shipping_area,grand_total,created_at',
            'orderItem:id,order_id,product_name,variant_name,sku,quantity,unit_price,line_total,is_refundable,return_window_days',
            'pickupShipment.events',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncPickupShipment(ReturnRequest $returnRequest, array $validated, Request $request): void
    {
        $status = ReturnStatus::from($validated['status']);

        if (! in_array($status, [ReturnStatus::Approved, ReturnStatus::Received, ReturnStatus::Refunded], true)) {
            return;
        }

        $order = $returnRequest->order;

        if (! $order) {
            return;
        }

        $shipmentStatus = match ($status) {
            ReturnStatus::Received, ReturnStatus::Refunded => ShipmentStatus::Delivered,
            default => ShipmentStatus::Pending,
        };

        $shipment = $returnRequest->pickupShipment()->firstOrCreate([
            'order_id' => $order->id,
            'direction' => 'return_pickup',
        ], [
            'status' => ShipmentStatus::Pending,
            'courier_name' => $validated['pickup_courier_name'] ?? null,
            'tracking_number' => $validated['pickup_tracking_number'] ?? null,
            'tracking_url' => $validated['pickup_tracking_url'] ?? null,
            'delivery_charge' => 0,
            'cod_charge' => 0,
            'cod_amount' => 0,
            'recipient_name' => $order->customer_name,
            'recipient_phone' => $order->customer_phone,
            'recipient_district' => $order->shipping_district,
            'recipient_area' => $order->shipping_area,
            'recipient_address' => $order->shipping_address['address_line'] ?? $order->shipping_district,
            'estimated_delivery_at' => now()->addDays(3),
            'metadata' => [
                'return_number' => $returnRequest->return_number,
                'order_item_id' => $returnRequest->order_item_id,
            ],
        ]);

        $shipment->update([
            'status' => $shipmentStatus,
            'courier_name' => $validated['pickup_courier_name'] ?? $shipment->courier_name,
            'tracking_number' => $validated['pickup_tracking_number'] ?? $shipment->tracking_number,
            'tracking_url' => $validated['pickup_tracking_url'] ?? $shipment->tracking_url,
            'delivered_at' => $shipmentStatus === ShipmentStatus::Delivered ? ($shipment->delivered_at ?? now()) : $shipment->delivered_at,
            'admin_note' => $validated['pickup_note'] ?? $shipment->admin_note,
        ]);

        $shipment->events()->create([
            'user_id' => $request->user()?->id,
            'status' => $shipmentStatus,
            'event_type' => $shipmentStatus === ShipmentStatus::Delivered ? 'return_received' : 'return_pickup_created',
            'location' => $order->shipping_district,
            'message' => $validated['pickup_note'] ?? ($shipmentStatus === ShipmentStatus::Delivered ? 'Returned item received for warehouse review.' : 'Return pickup has been scheduled.'),
            'is_public' => true,
            'metadata' => [
                'return_number' => $returnRequest->return_number,
                'return_status' => $status->value,
            ],
            'occurred_at' => now(),
        ]);
    }
}
