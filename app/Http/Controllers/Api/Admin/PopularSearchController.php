<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminPopularSearchResource;
use App\Models\PopularSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class PopularSearchController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $searches = PopularSearch::query()
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('label_en', 'like', "%{$search}%")
                        ->orWhere('label_bn', 'like', "%{$search}%")
                        ->orWhere('keyword', 'like', "%{$search}%")
                        ->orWhere('href', 'like', "%{$search}%");
                });
            })
            ->when(($validated['status'] ?? null) === 'active', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when(($validated['status'] ?? null) === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->latest('updated_at')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return AdminPopularSearchResource::collection($searches);
    }

    public function store(Request $request): AdminPopularSearchResource
    {
        $popularSearch = PopularSearch::query()->create($this->validatedData($request));

        return AdminPopularSearchResource::make($popularSearch);
    }

    public function update(Request $request, PopularSearch $popularSearch): AdminPopularSearchResource
    {
        $popularSearch->update($this->validatedData($request));

        return AdminPopularSearchResource::make($popularSearch->refresh());
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'label_en' => ['required', 'string', 'max:80'],
            'label_bn' => ['nullable', 'string', 'max:80'],
            'keyword' => ['required', 'string', 'max:120'],
            'href' => ['required', 'string', 'max:255', 'starts_with:/products'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'click_count' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
