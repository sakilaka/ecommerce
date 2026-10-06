<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 12);

        $products = Product::query()
            ->with(['category:id,name,slug', 'brand:id,name,slug,is_official'])
            ->withCount([
                'variants',
                'variants as low_stock_variants_count' => fn (Builder $query): Builder => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold'),
            ])
            ->withSum('variants', 'stock_quantity')
            ->withSum('variants', 'reserved_quantity')
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->where('status', $validated['status']))
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhereHas('category', fn (Builder $category): Builder => $category->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('brand', fn (Builder $brand): Builder => $brand->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('updated_at')
            ->latest('id')
            ->paginate($perPage);

        return AdminProductResource::collection($products);
    }

    public function options(): JsonResponse
    {
        return response()->json([
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'brands' => Brand::query()
                ->where('is_active', true)
                ->orderByDesc('is_official')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'is_official']),
            'statuses' => collect(ProductStatus::cases())->map(fn (ProductStatus $status): array => [
                'value' => $status->value,
                'label' => Str::headline($status->value),
            ])->values(),
        ]);
    }

    public function store(Request $request): AdminProductResource
    {
        $validated = $this->validatedData($request);

        $product = DB::transaction(function () use ($validated): Product {
            $product = Product::query()->create($this->productPayload($validated));
            $this->syncVariants($product, $validated['variants']);
            $this->syncImages($product, $validated['images'] ?? []);

            return $this->freshProduct($product);
        });

        return AdminProductResource::make($product);
    }

    public function show(Product $product): AdminProductResource
    {
        return AdminProductResource::make($this->freshProduct($product));
    }

    public function update(Request $request, Product $product): AdminProductResource
    {
        $validated = $this->validatedData($request, $product);

        $product = DB::transaction(function () use ($product, $validated): Product {
            $product->update($this->productPayload($validated, $product));
            $this->syncVariants($product, $validated['variants']);
            $this->syncImages($product, $validated['images'] ?? []);

            return $this->freshProduct($product);
        });

        return AdminProductResource::make($product);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product)],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product)],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:180'],
            'price' => ['required', 'integer', 'min:1'],
            'compare_at_price' => ['nullable', 'integer', 'min:1', 'gte:price'],
            'cost_price' => ['nullable', 'integer', 'min:1'],
            'discount_percent' => ['nullable', 'integer', 'min:0', 'max:90'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_ends_at' => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'is_featured' => ['required', 'boolean'],
            'is_refundable' => ['required', 'boolean'],
            'return_window_days' => ['required', 'integer', 'min:0', 'max:30'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999.999'],
            'variants' => ['required', 'array', 'min:1', 'max:20'],
            'variants.*.id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->when($product, fn ($rule) => $rule->where('product_id', $product->id))],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.sku' => ['required', 'string', 'max:255'],
            'variants.*.price' => ['nullable', 'integer', 'min:1'],
            'variants.*.compare_at_price' => ['nullable', 'integer', 'min:1'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.reserved_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['required', 'integer', 'min:0'],
            'variants.*.oversell_allowed' => ['required', 'boolean'],
            'variants.*.is_active' => ['required', 'boolean'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*.path' => ['required_with:images', 'string', 'max:255'],
            'images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'images.*.is_primary' => ['required_with:images', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function productPayload(array $validated, ?Product $product = null): array
    {
        $slug = filled($validated['slug'] ?? null)
            ? Str::slug($validated['slug'])
            : ($product?->slug ?? $this->uniqueSlug($validated['name']));

        return [
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'name' => $validated['name'],
            'slug' => $slug,
            'sku' => filled($validated['sku'] ?? null) ? $validated['sku'] : null,
            'status' => $validated['status'],
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'price' => $validated['price'],
            'compare_at_price' => $validated['compare_at_price'] ?? null,
            'cost_price' => $validated['cost_price'] ?? null,
            'discount_percent' => $validated['discount_percent'] ?? null,
            'sale_starts_at' => $validated['sale_starts_at'] ?? null,
            'sale_ends_at' => $validated['sale_ends_at'] ?? null,
            'is_featured' => $validated['is_featured'],
            'is_refundable' => $validated['is_refundable'],
            'return_window_days' => $validated['return_window_days'],
            'weight_kg' => $validated['weight_kg'] ?? null,
            'published_at' => $validated['status'] === ProductStatus::Active->value ? ($product?->published_at ?? now()) : null,
            'meta' => [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $keptIds = collect($variants)->pluck('id')->filter()->values();
        $product->variants()->when($keptIds->isNotEmpty(), fn (Builder $query): Builder => $query->whereNotIn('id', $keptIds))->delete();

        foreach ($variants as $variant) {
            ProductVariant::query()->updateOrCreate(
                ['id' => $variant['id'] ?? null, 'product_id' => $product->id],
                [
                    'name' => $variant['name'],
                    'sku' => $variant['sku'],
                    'options' => [],
                    'price' => $variant['price'] ?? null,
                    'compare_at_price' => $variant['compare_at_price'] ?? null,
                    'stock_quantity' => $variant['stock_quantity'],
                    'reserved_quantity' => $variant['reserved_quantity'],
                    'low_stock_threshold' => $variant['low_stock_threshold'],
                    'oversell_allowed' => $variant['oversell_allowed'],
                    'is_active' => $variant['is_active'],
                ]
            );
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $images
     */
    private function syncImages(Product $product, array $images): void
    {
        $product->images()->delete();

        foreach (array_values($images) as $index => $image) {
            $product->images()->create([
                'path' => $image['path'],
                'alt_text' => $image['alt_text'] ?? $product->name,
                'sort_order' => $index,
                'is_primary' => (bool) $image['is_primary'],
            ]);
        }
    }

    private function freshProduct(Product $product): Product
    {
        return $product->refresh()
            ->load(['category:id,name,slug', 'brand:id,name,slug,is_official', 'variants', 'images'])
            ->loadCount([
                'variants',
                'variants as low_stock_variants_count' => fn (Builder $query): Builder => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold'),
            ])
            ->loadSum('variants', 'stock_quantity')
            ->loadSum('variants', 'reserved_quantity');
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $count = 2;

        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$count;
            $count++;
        }

        return $slug;
    }
}
