<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ProductReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminProductReviewResource;
use App\Models\AuditLog;
use App\Models\ProductReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProductReviewController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ProductReviewStatus::class)],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'verified' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $reviews = ProductReview::query()
            ->with([
                'product:id,name,slug,sku',
                'user:id,name,email,phone',
                'orderItem:id,order_id,product_name,variant_name,sku',
                'moderatedBy:id,name,email',
            ])
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->where('status', $validated['status']))
            ->when(isset($validated['rating']), fn (Builder $query): Builder => $query->where('rating', $validated['rating']))
            ->when(isset($validated['verified']), fn (Builder $query): Builder => $query->where('is_verified_purchase', $validated['verified']))
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhereHas('product', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return AdminProductReviewResource::collection($reviews);
    }

    public function show(ProductReview $review): AdminProductReviewResource
    {
        return AdminProductReviewResource::make($this->loadReview($review));
    }

    public function update(Request $request, ProductReview $review): AdminProductReviewResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ProductReviewStatus::class)],
            'is_verified_purchase' => ['required', 'boolean'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $before = $this->auditValues($review);

        $review->update([
            'status' => ProductReviewStatus::from($validated['status']),
            'is_verified_purchase' => $validated['is_verified_purchase'],
            'admin_note' => $validated['admin_note'] ?? null,
            'moderated_by' => $request->user()?->id,
            'moderated_at' => now(),
        ]);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'product_review.updated',
            'auditable_type' => ProductReview::class,
            'auditable_id' => $review->id,
            'old_values' => $before,
            'new_values' => $this->auditValues($review),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return AdminProductReviewResource::make($this->loadReview($review->refresh()));
    }

    private function loadReview(ProductReview $review): ProductReview
    {
        return $review->load([
            'product:id,name,slug,sku',
            'user:id,name,email,phone',
            'orderItem:id,order_id,product_name,variant_name,sku',
            'moderatedBy:id,name,email',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(ProductReview $review): array
    {
        return [
            'status' => $review->status?->value ?? $review->status,
            'is_verified_purchase' => $review->is_verified_purchase,
            'admin_note' => $review->admin_note,
            'moderated_by' => $review->moderated_by,
            'moderated_at' => $review->moderated_at?->toISOString(),
        ];
    }
}
