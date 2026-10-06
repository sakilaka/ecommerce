<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\OrderStatus;
use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReturnRequest\StoreReturnRequest;
use App\Http\Resources\CustomerReturnRequestResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class ReturnRequestController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CustomerReturnRequestResource::collection(
            ReturnRequest::query()
                ->with(['order:id,order_number,status,grand_total,created_at', 'orderItem:id,order_id,product_name,variant_name,sku,quantity,unit_price,line_total,is_refundable,return_window_days', 'pickupShipment.events'])
                ->where('user_id', request()->user()->id)
                ->latest()
                ->limit(20)
                ->get(),
        );
    }

    public function store(StoreReturnRequest $request): CustomerReturnRequestResource|JsonResponse
    {
        $validated = $request->validated();
        $order = Order::query()
            ->with('items')
            ->where('user_id', $request->user()->id)
            ->findOrFail($validated['order_id']);

        if ($order->status !== OrderStatus::Delivered) {
            return response()->json(['message' => 'Returns can only be requested after delivery.'], 422);
        }

        $orderItem = $order->items->firstWhere('id', $validated['order_item_id']);

        if (! $orderItem instanceof OrderItem) {
            return response()->json(['message' => 'Selected item does not belong to this order.'], 422);
        }

        if (! $orderItem->is_refundable) {
            return response()->json(['message' => 'This product is not refundable.'], 422);
        }

        if (! $this->isWithinReturnWindow($order, $orderItem)) {
            return response()->json(['message' => 'The return window for this item has expired.'], 422);
        }

        $hasOpenReturn = ReturnRequest::query()
            ->where('order_item_id', $orderItem->id)
            ->whereNotIn('status', [ReturnStatus::Rejected->value])
            ->exists();

        if ($hasOpenReturn) {
            return response()->json(['message' => 'A return request already exists for this item.'], 422);
        }

        $returnRequest = ReturnRequest::query()->create([
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'user_id' => $request->user()->id,
            'return_number' => $this->nextReturnNumber(),
            'status' => ReturnStatus::Requested,
            'refund_amount' => $orderItem->line_total,
            'reason' => $validated['reason'],
        ]);

        return CustomerReturnRequestResource::make($returnRequest->load(['order', 'orderItem', 'pickupShipment.events']));
    }

    private function isWithinReturnWindow(Order $order, OrderItem $orderItem): bool
    {
        $windowDays = max(0, (int) $orderItem->return_window_days);

        if ($windowDays === 0) {
            return false;
        }

        $windowStartsAt = $order->delivered_at ?? $order->created_at;

        return $windowStartsAt?->copy()->addDays($windowDays)->endOfDay()->isFuture() ?? false;
    }

    private function nextReturnNumber(): string
    {
        return 'RET-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
    }
}
