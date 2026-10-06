<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminBrandResource;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'official' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $brands = Brand::query()
            ->withCount('products')
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->where('is_active', $validated['status'] === 'active'))
            ->when(array_key_exists('official', $validated), fn (Builder $query): Builder => $query->where('is_official', $validated['official']))
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];
                $query->where(fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
            })
            ->orderByDesc('is_official')
            ->orderBy('name')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return AdminBrandResource::collection($brands);
    }

    public function store(Request $request): AdminBrandResource
    {
        $validated = $this->validatedData($request);
        $validated['slug'] = $this->resolveSlug($validated['slug'] ?? null, $validated['name']);

        $brand = Brand::query()->create($validated);

        return AdminBrandResource::make($this->freshBrand($brand));
    }

    public function update(Request $request, Brand $brand): AdminBrandResource
    {
        $validated = $this->validatedData($request, $brand);
        $validated['slug'] = $this->resolveSlug($validated['slug'] ?? null, $validated['name'], $brand);

        $brand->update($validated);

        return AdminBrandResource::make($this->freshBrand($brand));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Brand $brand = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($brand)],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:180'],
            'is_official' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function freshBrand(Brand $brand): Brand
    {
        return $brand->refresh()->loadCount('products');
    }

    private function resolveSlug(?string $slug, string $name, ?Brand $brand = null): string
    {
        $baseSlug = Str::slug(filled($slug) ? $slug : $name);
        $candidate = $baseSlug;
        $count = 2;

        while (Brand::query()->where('slug', $candidate)->when($brand, fn (Builder $query): Builder => $query->whereKeyNot($brand->id))->exists()) {
            $candidate = $baseSlug.'-'.$count;
            $count++;
        }

        return $candidate;
    }
}
