<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerOrderController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return OrderResource::collection(
            request()->user()
                ->orders()
                ->with(['items', 'payments', 'shipments.events'])
                ->latest()
                ->limit(10)
                ->get(),
        );
    }

    public function show(Order $order): OrderResource
    {
        if ($order->user_id !== request()->user()->id) {
            abort(404);
        }

        return OrderResource::make($order->load(['items', 'payments', 'shipments.events']));
    }
}
