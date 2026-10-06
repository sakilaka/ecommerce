<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminCategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $categories = Category::query()
            ->with('parent:id,name,slug')
            ->withCount(['products', 'children'])
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->where('is_active', $validated['status'] === 'active'))
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];
                $query->where(fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return AdminCategoryResource::collection($categories);
    }

    public function store(Request $request): AdminCategoryResource
    {
        $validated = $this->validatedData($request);
        $validated['slug'] = $this->resolveSlug($validated['slug'] ?? null, $validated['name']);

        $category = Category::query()->create($validated);

        return AdminCategoryResource::make($this->freshCategory($category));
    }

    public function update(Request $request, Category $category): AdminCategoryResource
    {
        $validated = $this->validatedData($request, $category);
        $validated['slug'] = $this->resolveSlug($validated['slug'] ?? null, $validated['name'], $category);

        $category->update($validated);

        return AdminCategoryResource::make($this->freshCategory($category));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true), Rule::notIn([$category?->id])],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category)],
            'image_path' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:180'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function freshCategory(Category $category): Category
    {
        return $category->refresh()->load('parent:id,name,slug')->loadCount(['products', 'children']);
    }

    private function resolveSlug(?string $slug, string $name, ?Category $category = null): string
    {
        $baseSlug = Str::slug(filled($slug) ? $slug : $name);
        $candidate = $baseSlug;
        $count = 2;

        while (Category::query()->where('slug', $candidate)->when($category, fn (Builder $query): Builder => $query->whereKeyNot($category->id))->exists()) {
            $candidate = $baseSlug.'-'.$count;
            $count++;
        }

        return $candidate;
    }
}
