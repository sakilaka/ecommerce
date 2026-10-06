<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $recentOrders = Order::query()
            ->select(['id', 'order_number', 'customer_name', 'status', 'payment_status', 'payment_method', 'grand_total', 'created_at'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'status' => $order->status?->value ?? $order->status,
                'payment_status' => $order->payment_status?->value ?? $order->payment_status,
                'payment_method' => $order->payment_method?->value ?? $order->payment_method,
                'grand_total' => $order->grand_total,
                'created_at' => $order->created_at?->toISOString(),
            ]);

        $stockAlerts = ProductVariant::query()
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
            ]);

        return response()->json([
            'data' => [
                'stats' => [
                    'orders_total' => Order::query()->count(),
                    'orders_pending' => Order::query()->where('status', OrderStatus::Pending->value)->count(),
                    'orders_processing' => Order::query()->whereIn('status', [OrderStatus::Confirmed->value, OrderStatus::Processing->value, OrderStatus::Shipped->value])->count(),
                    'orders_month' => Order::query()->where('created_at', '>=', now()->startOfMonth())->count(),
                    'revenue_total' => Order::query()->where('payment_status', PaymentStatus::Paid->value)->sum('grand_total'),
                    'revenue_today' => Order::query()->whereDate('created_at', today())->sum('grand_total'),
                    'products_active' => Product::query()->where('status', ProductStatus::Active->value)->count(),
                    'products_draft' => Product::query()->where('status', ProductStatus::Draft->value)->count(),
                    'low_stock_variants' => ProductVariant::query()->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
                    'customers_total' => User::query()->where('role', UserRole::Customer->value)->count(),
                    'customers_month' => User::query()->where('role', UserRole::Customer->value)->where('created_at', '>=', now()->startOfMonth())->count(),
                    'categories_total' => Category::query()->count(),
                    'brands_total' => Brand::query()->count(),
                    'return_requests' => ReturnRequest::query()->count(),
                ],
                'sales_trend' => $this->salesTrend(),
                'recent_buyers' => $this->recentBuyers(),
                'recent_orders' => $recentOrders,
                'stock_alerts' => $stockAlerts,
                'payment_mix' => [
                    'paid' => Order::query()->where('payment_status', PaymentStatus::Paid->value)->count(),
                    'unpaid' => Order::query()->where('payment_status', PaymentStatus::Unpaid->value)->count(),
                    'pending' => Order::query()->where('payment_status', PaymentStatus::Pending->value)->count(),
                    'failed' => Order::query()->where('payment_status', PaymentStatus::Failed->value)->count(),
                ],
            ],
        ]);
    }

    /**
     * @return list<array{key: string, label: string, total: int, orders_count: int}>
     */
    private function salesTrend(): array
    {
        $start = now()->subMonths(11)->startOfMonth();
        $expression = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $rows = Order::query()
            ->selectRaw($expression.' as month_key, SUM(grand_total) as total, COUNT(*) as orders_count')
            ->where('created_at', '>=', $start)
            ->groupByRaw($expression)
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        return collect(range(11, 0))->map(function (int $monthsAgo) use ($rows): array {
            $month = now()->subMonths($monthsAgo)->startOfMonth();
            $key = $month->format('Y-m');
            $row = $rows->get($key);

            return [
                'key' => $key,
                'label' => $month->format('M'),
                'total' => (int) ($row->total ?? 0),
                'orders_count' => (int) ($row->orders_count ?? 0),
            ];
        })->all();
    }

    /**
     * @return list<array{id: int, name: ?string, email: ?string, status: mixed, grand_total: int, created_at: ?string}>
     */
    private function recentBuyers(): array
    {
        return Order::query()
            ->latest()
            ->limit(16)
            ->get(['id', 'customer_name', 'customer_email', 'grand_total', 'status', 'created_at'])
            ->unique(fn (Order $order): string => strtolower((string) ($order->customer_email ?: $order->customer_name)))
            ->take(5)
            ->values()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'status' => $order->status?->value ?? $order->status,
                'grand_total' => $order->grand_total,
                'created_at' => $order->created_at?->toISOString(),
            ])
            ->all();
    }
}
