<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    public function categories(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function brands(): AnonymousResourceCollection
    {
        $brands = Brand::query()
            ->where('is_active', true)
            ->orderByDesc('is_official')
            ->orderBy('name')
            ->get();

        return BrandResource::collection($brands);
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $perPage = min(max($request->integer('per_page', 12), 1), 48);

        $products = Product::query()
            ->with(['category', 'brand', 'images'])
            ->where('status', ProductStatus::Active)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->when($request->filled('category'), function (Builder $query) use ($request): void {
                $query->whereHas('category', fn (Builder $category): Builder => $category->where('slug', $request->string('category')->toString()));
            })
            ->when($request->filled('brand'), function (Builder $query) use ($request): void {
                $query->whereHas('brand', fn (Builder $brand): Builder => $brand->where('slug', $request->string('brand')->toString()));
            })
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $this->applySearch($query, $request->string('q')->toString());
            })
            ->when($request->filled('topic'), function (Builder $query) use ($request): void {
                $this->applySearch($query, Str::of($request->string('topic')->toString())->replace('-', ' ')->toString());
            })
            ->when($request->filled('collection'), function (Builder $query) use ($request): void {
                $this->applySearch($query, Str::of($request->string('collection')->toString())->replace('-', ' ')->toString());
            })
            ->when($request->filled('campaign'), function (Builder $query) use ($request): void {
                $campaign = $request->string('campaign')->toString();

                if (in_array($campaign, ['flash-sale', 'eid-mega-sale', 'deals'], true)) {
                    $query->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price');
                }
            })
            ->when($request->filled('price_min'), fn (Builder $query): Builder => $query->where('price', '>=', $this->takaToStoredAmount($request->integer('price_min'))))
            ->when($request->filled('price_max'), fn (Builder $query): Builder => $query->where('price', '<=', $this->takaToStoredAmount($request->integer('price_max'))));

        match ($request->string('sort', 'latest')->toString()) {
            'price_low' => $products->orderBy('price')->orderByDesc('id'),
            'price_high' => $products->orderByDesc('price')->orderByDesc('id'),
            'featured' => $products->orderByDesc('is_featured')->orderByDesc('published_at')->orderByDesc('id'),
            default => $products->orderByDesc('published_at')->orderByDesc('id'),
        };

        return ProductResource::collection($products->paginate($perPage));
    }

    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $query) use ($search): void {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('short_description', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhereHas('category', fn (Builder $category): Builder => $category->where('name', 'like', "%{$search}%"))
                ->orWhereHas('brand', fn (Builder $brand): Builder => $brand->where('name', 'like', "%{$search}%"));
        });
    }

    private function takaToStoredAmount(int $amount): int
    {
        return $amount * 100;
    }

    public function product(Product $product): ProductResource
    {
        abort_unless(
            $product->status === ProductStatus::Active && $product->published_at !== null && $product->published_at->isPast(),
            404,
        );

        return ProductResource::make($product->load([
            'category',
            'brand',
            'images',
            'variants' => fn (HasMany $query): HasMany => $query->where('is_active', true),
        ]));
    }
}
