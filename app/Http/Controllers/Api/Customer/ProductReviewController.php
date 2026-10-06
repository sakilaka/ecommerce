<?php

namespace App\Http\Controllers\Api\Customer;

use App\Enums\OrderStatus;
use App\Enums\ProductReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreProductReviewRequest;
use App\Http\Resources\CustomerProductReviewResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductReviewController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CustomerProductReviewResource::collection(
            ProductReview::query()
                ->with(['product:id,name,slug,sku', 'orderItem:id,order_id,product_name,variant_name,sku'])
                ->where('user_id', request()->user()->id)
                ->latest()
                ->limit(20)
                ->get(),
        );
    }

    public function store(StoreProductReviewRequest $request): CustomerProductReviewResource|JsonResponse
    {
        $validated = $request->validated();
        $order = Order::query()
            ->with('items.product')
            ->where('user_id', $request->user()->id)
            ->findOrFail($validated['order_id']);

        if ($order->status !== OrderStatus::Delivered) {
            return response()->json(['message' => 'Reviews can only be submitted after delivery.'], 422);
        }

        $orderItem = $order->items->firstWhere('id', $validated['order_item_id']);

        if (! $orderItem instanceof OrderItem) {
            return response()->json(['message' => 'Selected item does not belong to this order.'], 422);
        }

        $hasReview = ProductReview::query()
            ->where('order_item_id', $orderItem->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($hasReview) {
            return response()->json(['message' => 'You already reviewed this item.'], 422);
        }

        $review = ProductReview::query()->create([
            'product_id' => $orderItem->product_id,
            'user_id' => $request->user()->id,
            'order_item_id' => $orderItem->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'status' => ProductReviewStatus::Pending,
            'is_verified_purchase' => true,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'customer_phone' => $order->customer_phone,
        ]);

        return CustomerProductReviewResource::make($review->load(['product', 'orderItem']))
            ->response()
            ->setStatusCode(201);
    }
}
