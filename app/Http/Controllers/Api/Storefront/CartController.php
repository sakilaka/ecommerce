<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cart\AddCartItemRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function show(Request $request): CartResource
    {
        return CartResource::make($this->resolveCart($request)->load($this->cartRelations()));
    }

    public function addItem(AddCartItemRequest $request): CartResource|JsonResponse
    {
        $validated = $request->validated();
        $product = Product::query()
            ->with(['images', 'category', 'brand'])
            ->whereKey($validated['product_id'])
            ->firstOrFail();

        if (! $this->isPurchasable($product)) {
            return response()->json(['message' => 'This product is not available for purchase.'], 422);
        }

        $variant = $this->resolveVariant($product, $validated['product_variant_id'] ?? null);

        if (! $variant) {
            return response()->json(['message' => 'This product variant is not available.'], 422);
        }

        $quantity = (int) $validated['quantity'];

        if (! $this->hasAvailableStock($variant, $quantity)) {
            return response()->json(['message' => 'Requested quantity is not available.'], 422);
        }

        $cart = DB::transaction(function () use ($request, $product, $variant, $quantity): Cart {
            $cart = $this->resolveCart($request);
            $existingItem = $cart->items()
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variant->id)
                ->lockForUpdate()
                ->first();

            $newQuantity = $quantity + ($existingItem?->quantity ?? 0);

            if (! $this->hasAvailableStock($variant, $newQuantity)) {
                abort(response()->json(['message' => 'Requested quantity is not available.'], 422));
            }

            $cart->items()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                ],
                [
                    'quantity' => $newQuantity,
                    'unit_price' => $variant->price ?? $product->price,
                    'price_snapshot' => $this->priceSnapshot($product, $variant),
                ],
            );

            return $cart;
        });

        return CartResource::make($cart->load($this->cartRelations()));
    }

    public function updateItem(UpdateCartItemRequest $request, CartItem $cartItem): CartResource|JsonResponse
    {
        $cart = $this->resolveCart($request);

        if ($cartItem->cart_id !== $cart->id) {
            abort(404);
        }

        $cartItem->load(['product', 'productVariant']);
        $quantity = (int) $request->validated('quantity');

        if ($cartItem->productVariant && ! $this->hasAvailableStock($cartItem->productVariant, $quantity)) {
            return response()->json(['message' => 'Requested quantity is not available.'], 422);
        }

        $cartItem->update(['quantity' => $quantity]);

        return CartResource::make($cart->load($this->cartRelations()));
    }

    public function removeItem(Request $request, CartItem $cartItem): CartResource
    {
        $cart = $this->resolveCart($request);

        if ($cartItem->cart_id !== $cart->id) {
            abort(404);
        }

        $cartItem->delete();

        return CartResource::make($cart->load($this->cartRelations()));
    }

    public function clear(Request $request): CartResource
    {
        $cart = $this->resolveCart($request);
        $cart->items()->delete();

        return CartResource::make($cart->load($this->cartRelations()));
    }

    /**
     * @return array<int, string>
     */
    private function cartRelations(): array
    {
        return ['items.product.category', 'items.product.brand', 'items.product.images', 'items.productVariant'];
    }

    private function resolveCart(Request $request): Cart
    {
        $user = $request->user();
        $sessionId = $request->header('X-Cart-Session') ?: $request->input('cart_session_id') ?: Str::uuid()->toString();

        if ($user) {
            return Cart::query()->firstOrCreate(
                ['user_id' => $user->id, 'status' => CartStatus::Active],
                ['session_id' => $sessionId, 'expires_at' => now()->addDays(30)],
            );
        }

        return Cart::query()->firstOrCreate(
            ['session_id' => $sessionId, 'status' => CartStatus::Active],
            ['expires_at' => now()->addDays(14)],
        );
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        $query = $product->variants()->where('is_active', true);

        if ($variantId) {
            return $query->whereKey($variantId)->first();
        }

        return $query->orderBy('id')->first();
    }

    private function isPurchasable(Product $product): bool
    {
        return $product->status === ProductStatus::Active && ($product->published_at === null || $product->published_at->isPast());
    }

    private function hasAvailableStock(ProductVariant $variant, int $quantity): bool
    {
        if ($variant->oversell_allowed) {
            return true;
        }

        return ($variant->stock_quantity - $variant->reserved_quantity) >= $quantity;
    }

    /**
     * @return array<string, mixed>
     */
    private function priceSnapshot(Product $product, ProductVariant $variant): array
    {
        return [
            'product_name' => $product->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'unit_price' => $variant->price ?? $product->price,
            'compare_at_price' => $variant->compare_at_price ?? $product->compare_at_price,
            'discount_percent' => $product->discount_percent,
        ];
    }
}
