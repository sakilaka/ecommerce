<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\TrackOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;

class OrderTrackingController extends Controller
{
    public function show(TrackOrderRequest $request): OrderResource
    {
        $validated = $request->validated();

        $order = Order::query()
            ->with(['items', 'payments', 'shipments.events'])
            ->where('order_number', $validated['order_number'])
            ->where('customer_phone', $validated['phone'])
            ->firstOrFail();

        return OrderResource::make($order);
    }
}
