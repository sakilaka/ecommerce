<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\ProductReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\ProductReviewResource;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductReviewController extends Controller
{
    public function index(Product $product): AnonymousResourceCollection
    {
        $reviews = ProductReview::query()
            ->with('user:id,name')
            ->whereBelongsTo($product)
            ->where('status', ProductReviewStatus::Approved)
            ->latest('created_at')
            ->latest('id')
            ->paginate(6);

        $approvedReviews = ProductReview::query()
            ->whereBelongsTo($product)
            ->where('status', ProductReviewStatus::Approved);

        return ProductReviewResource::collection($reviews)->additional([
            'summary' => [
                'average_rating' => round((float) $approvedReviews->avg('rating'), 1),
                'reviews_count' => (clone $approvedReviews)->count(),
                'verified_count' => (clone $approvedReviews)->where('is_verified_purchase', true)->count(),
            ],
        ]);
    }
}
