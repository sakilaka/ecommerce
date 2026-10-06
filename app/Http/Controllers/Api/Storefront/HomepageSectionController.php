<?php

namespace App\Http\Controllers\Api\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Storefront\HomepageSectionResource;
use App\Models\Coupon;
use App\Models\HomepageSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class HomepageSectionController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'type' => ['nullable', 'string', 'max:80'],
        ]);

        $sections = HomepageSection::query()
            ->activeForStorefront()
            ->when(isset($validated['type']), fn ($query) => $query->where('type', $validated['type']))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $couponCodes = $sections
            ->map(fn (HomepageSection $section): ?string => $this->couponCode($section))
            ->filter()
            ->unique()
            ->values();

        $coupons = Coupon::query()
            ->running()
            ->whereIn('code', $couponCodes)
            ->get()
            ->keyBy('code');

        $sections->each(function (HomepageSection $section) use ($coupons): void {
            $couponCode = $this->couponCode($section);
            $section->setAttribute('promotion_coupon', $couponCode ? $coupons->get($couponCode) : null);
        });

        return HomepageSectionResource::collection($sections);
    }

    private function couponCode(HomepageSection $section): ?string
    {
        $code = $section->settings['coupon_code'] ?? null;

        return is_string($code) && $code !== '' ? Str::upper($code) : null;
    }
}
