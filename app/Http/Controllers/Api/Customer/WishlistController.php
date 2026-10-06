<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\WishlistItemResource;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class WishlistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $items = WishlistItem::query()
            ->whereBelongsTo($request->user())
            ->with(['product:id,name,slug,sku,price,compare_at_price,status', 'productVariant:id,name,sku,stock_quantity'])
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->whereHas('product', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            })
            ->latest('created_at')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return WishlistItemResource::collection($items);
    }

    public function store(Request $request): WishlistItemResource
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')],
            'source' => ['nullable', 'string', 'max:60'],
            'context' => ['nullable', 'array'],
        ]);

        if (isset($validated['product_variant_id'])) {
            abort_unless(ProductVariant::query()->whereKey($validated['product_variant_id'])->where('product_id', $validated['product_id'])->exists(), 422, 'The selected variant does not belong to this product.');
        }

        $item = WishlistItem::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_id' => $validated['product_id'],
            ],
            [
                'product_variant_id' => $validated['product_variant_id'] ?? null,
                'source' => $validated['source'] ?? 'storefront',
                'context' => $validated['context'] ?? [],
            ]
        );

        return WishlistItemResource::make($item->load(['product:id,name,slug,sku,price,compare_at_price,status', 'productVariant:id,name,sku,stock_quantity']));
    }

    public function destroy(Request $request, WishlistItem $wishlistItem): JsonResponse
    {
        abort_unless($wishlistItem->user_id === $request->user()->id, 404);

        $wishlistItem->delete();

        return response()->json(['message' => 'Wishlist item removed.']);
    }
}
