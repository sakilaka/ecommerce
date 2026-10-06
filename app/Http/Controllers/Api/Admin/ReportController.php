<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\HomepageSection;
use App\Models\NewsletterLead;
use App\Models\Order;
use App\Models\PopularSearch;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['7', '30', '90'])],
        ]);

        $days = (int) ($validated['period'] ?? 30);
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();
        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $start->copy()->subSecond();

        return response()->json([
            'data' => [
                'period' => [
                    'days' => $days,
                    'start' => $start->toISOString(),
                    'end' => $end->toISOString(),
                ],
                'summary' => $this->summary($start, $end, $previousStart, $previousEnd),
                'sales_trend' => $this->salesTrend($start, $end),
                'order_status' => $this->orderStatus($start, $end),
                'payment_status' => $this->paymentStatus($start, $end),
                'top_products' => $this->topProducts($start, $end),
                'stock_risk' => $this->stockRisk(),
                'coupon_performance' => $this->couponPerformance($start, $end),
                'marketing_insights' => $this->marketingInsights($start, $end),
            ],
        ]);
    }

    /**
     * @return array<string, int|float>
     */
    private function summary(Carbon $start, Carbon $end, Carbon $previousStart, Carbon $previousEnd): array
    {
        $currentRevenue = (int) Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', PaymentStatus::Paid->value)
            ->sum('grand_total');

        $previousRevenue = (int) Order::query()
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->where('payment_status', PaymentStatus::Paid->value)
            ->sum('grand_total');

        $orders = Order::query()->whereBetween('created_at', [$start, $end])->count();
        $deliveredOrders = Order::query()->whereBetween('created_at', [$start, $end])->where('status', OrderStatus::Delivered->value)->count();
        $newCustomers = User::query()->where('role', UserRole::Customer->value)->whereBetween('created_at', [$start, $end])->count();
        $activeProducts = Product::query()->where('status', ProductStatus::Active->value)->count();

        return [
            'revenue' => $currentRevenue,
            'previous_revenue' => $previousRevenue,
            'revenue_change_percent' => $this->changePercent($currentRevenue, $previousRevenue),
            'orders' => $orders,
            'average_order_value' => $orders > 0 ? (int) round($currentRevenue / $orders) : 0,
            'delivered_orders' => $deliveredOrders,
            'delivery_rate_percent' => $orders > 0 ? round(($deliveredOrders / $orders) * 100, 1) : 0,
            'new_customers' => $newCustomers,
            'active_products' => $activeProducts,
            'low_stock_variants' => ProductVariant::query()->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function salesTrend(Carbon $start, Carbon $end): array
    {
        $rows = Order::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, COALESCE(SUM(grand_total), 0) as revenue')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $trend = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $row = $rows->get($key);
            $trend[] = [
                'date' => $key,
                'orders_count' => (int) ($row->orders_count ?? 0),
                'revenue' => (int) ($row->revenue ?? 0),
            ];
        }

        return $trend;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function orderStatus(Carbon $start, Carbon $end): array
    {
        return Order::query()
            ->selectRaw('status, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => ['status' => $row->status?->value ?? $row->status, 'total' => (int) $row->total])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paymentStatus(Carbon $start, Carbon $end): array
    {
        return Order::query()
            ->selectRaw('payment_status, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('payment_status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => ['status' => $row->payment_status?->value ?? $row->payment_status, 'total' => (int) $row->total])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topProducts(Carbon $start, Carbon $end): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$start, $end])
            ->selectRaw('order_items.product_id, order_items.product_name, SUM(order_items.quantity) as units_sold, SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('revenue')
            ->limit(6)
            ->get()
            ->map(fn ($row): array => [
                'product_id' => (int) $row->product_id,
                'product_name' => $row->product_name,
                'units_sold' => (int) $row->units_sold,
                'revenue' => (int) $row->revenue,
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function stockRisk(): array
    {
        return ProductVariant::query()
            ->with('product:id,name')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->limit(6)
            ->get()
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'product_name' => $variant->product?->name,
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'stock_quantity' => $variant->stock_quantity,
                'low_stock_threshold' => $variant->low_stock_threshold,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function couponPerformance(Carbon $start, Carbon $end): array
    {
        return Coupon::query()
            ->withCount(['orders' => fn ($query) => $query->whereBetween('created_at', [$start, $end])])
            ->withSum(['orders as orders_sum_discount_total' => fn ($query) => $query->whereBetween('created_at', [$start, $end])], 'discount_total')
            ->orderByDesc('orders_sum_discount_total')
            ->limit(5)
            ->get(['id', 'code', 'type', 'value', 'is_active'])
            ->map(fn (Coupon $coupon): array => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'type' => $coupon->type?->value ?? $coupon->type,
                'value' => $coupon->value,
                'is_active' => $coupon->is_active,
                'orders_count' => (int) $coupon->orders_count,
                'discount_total' => (int) ($coupon->orders_sum_discount_total ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function marketingInsights(Carbon $start, Carbon $end): array
    {
        $couponDiscount = (int) Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('coupon_id')
            ->sum('discount_total');

        $couponOrders = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('coupon_id')
            ->count();

        $orders = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $newLeads = NewsletterLead::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $activeLeads = NewsletterLead::query()
            ->active()
            ->count();

        return [
            'summary' => [
                'running_coupons' => Coupon::query()->running()->count(),
                'active_coupons' => Coupon::query()->where('is_active', true)->count(),
                'coupon_orders' => $couponOrders,
                'coupon_order_rate_percent' => $orders > 0 ? round(($couponOrders / $orders) * 100, 1) : 0,
                'coupon_discount_total' => $couponDiscount,
                'new_leads' => $newLeads,
                'active_leads' => $activeLeads,
                'lead_activation_rate_percent' => $activeLeads > 0 ? round(($newLeads / $activeLeads) * 100, 1) : 0,
                'active_popular_searches' => PopularSearch::query()->active()->count(),
                'active_campaign_sections' => HomepageSection::query()->activeForStorefront()->count(),
            ],
            'lead_sources' => $this->leadSources($start, $end),
            'lead_interests' => $this->leadInterests($start, $end),
            'popular_searches' => $this->popularSearches(),
            'campaign_sections' => $this->campaignSections(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function leadSources(Carbon $start, Carbon $end): array
    {
        return NewsletterLead::query()
            ->selectRaw('source, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('source')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn (NewsletterLead $lead): array => [
                'source' => $lead->source,
                'total' => (int) $lead->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function leadInterests(Carbon $start, Carbon $end): array
    {
        return NewsletterLead::query()
            ->selectRaw('COALESCE(interest, "general") as interest, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('COALESCE(interest, "general")')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'interest' => $row->interest,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function popularSearches(): array
    {
        return PopularSearch::query()
            ->active()
            ->orderByDesc('click_count')
            ->orderBy('sort_order')
            ->limit(6)
            ->get(['id', 'label_en', 'label_bn', 'keyword', 'href', 'click_count'])
            ->map(fn (PopularSearch $search): array => [
                'id' => $search->id,
                'label_en' => $search->label_en,
                'label_bn' => $search->label_bn,
                'keyword' => $search->keyword,
                'href' => $search->href,
                'click_count' => $search->click_count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function campaignSections(): array
    {
        return HomepageSection::query()
            ->activeForStorefront()
            ->orderBy('sort_order')
            ->limit(6)
            ->get(['id', 'key', 'type', 'title', 'sort_order', 'starts_at', 'ends_at'])
            ->map(fn (HomepageSection $section): array => [
                'id' => $section->id,
                'key' => $section->key,
                'type' => $section->type?->value ?? $section->type,
                'title' => $section->title,
                'sort_order' => $section->sort_order,
                'starts_at' => $section->starts_at?->toISOString(),
                'ends_at' => $section->ends_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    private function changePercent(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
