<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\PopularSearchResource;
use App\Models\PopularSearch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PopularSearchController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $searches = PopularSearch::query()
            ->active()
            ->orderBy('sort_order')
            ->orderByDesc('click_count')
            ->limit((int) ($validated['limit'] ?? 16))
            ->get();

        return PopularSearchResource::collection($searches);
    }
}
