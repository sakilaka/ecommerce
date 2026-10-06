<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\InventoryAdjustmentType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminInventoryResource;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'stock_status' => ['nullable', Rule::in(['low', 'healthy', 'out'])],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $variants = ProductVariant::query()
            ->with(['product:id,category_id,brand_id,name,sku,status', 'product.category:id,name,slug', 'product.brand:id,name,slug,is_official'])
            ->withCount('inventoryAdjustments as adjustments_count')
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhereHas('product', function (Builder $product) use ($search): void {
                            $product->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        });
                });
            })
            ->when(($validated['stock_status'] ?? null) === 'low', fn (Builder $query): Builder => $query->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold'))
            ->when(($validated['stock_status'] ?? null) === 'healthy', fn (Builder $query): Builder => $query->whereColumn('stock_quantity', '>', 'low_stock_threshold'))
            ->when(($validated['stock_status'] ?? null) === 'out', fn (Builder $query): Builder => $query->where('stock_quantity', '<=', 0))
            ->when(array_key_exists('active', $validated), fn (Builder $query): Builder => $query->where('is_active', $request->boolean('active')))
            ->orderBy('stock_quantity')
            ->latest('updated_at')
            ->paginate($perPage);

        return AdminInventoryResource::collection($variants);
    }

    public function store(Request $request, ProductVariant $productVariant): AdminInventoryResource
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(InventoryAdjustmentType::class)],
            'quantity_delta' => ['required', 'integer', 'between:-10000,10000', 'not_in:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $variant = DB::transaction(function () use ($productVariant, $request, $validated): ProductVariant {
            $variant = ProductVariant::query()->whereKey($productVariant->id)->lockForUpdate()->firstOrFail();
            $stockAfter = (int) $variant->stock_quantity + (int) $validated['quantity_delta'];

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity_delta' => 'Stock adjustment cannot reduce available stock below zero.',
                ]);
            }

            $variant->update(['stock_quantity' => $stockAfter]);
            $variant->inventoryAdjustments()->create([
                'user_id' => $request->user()?->id,
                'type' => $validated['type'],
                'quantity_delta' => $validated['quantity_delta'],
                'stock_after' => $stockAfter,
                'reference_type' => 'admin_adjustment',
                'reference_id' => $request->user()?->id,
                'note' => $validated['note'] ?? null,
            ]);

            return $this->freshVariant($variant);
        });

        return AdminInventoryResource::make($variant);
    }

    private function freshVariant(ProductVariant $productVariant): ProductVariant
    {
        return $productVariant->refresh()
            ->load(['product:id,category_id,brand_id,name,sku,status', 'product.category:id,name,slug', 'product.brand:id,name,slug,is_official'])
            ->loadCount('inventoryAdjustments as adjustments_count');
    }
}
