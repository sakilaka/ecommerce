<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminWishlistInsightResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WishlistInsightController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $products = Product::query()
            ->select(['id', 'category_id', 'brand_id', 'name', 'slug', 'sku', 'price', 'status'])
            ->with(['category:id,name', 'brand:id,name'])
            ->withCount([
                'wishlistItems',
                'wishlistItems as unique_customers_count' => fn (Builder $query): Builder => $query->selectRaw('count(distinct user_id)'),
            ])
            ->selectSub(function ($query): void {
                $query->from('wishlist_items')
                    ->selectRaw('max(created_at)')
                    ->whereColumn('wishlist_items.product_id', 'products.id');
            }, 'latest_wishlist_at')
            ->has('wishlistItems')
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('brand', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('wishlist_items_count')
            ->orderByDesc('latest_wishlist_at')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return AdminWishlistInsightResource::collection($products);
    }
}
