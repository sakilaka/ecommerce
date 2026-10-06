<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class FulfillmentReportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', Rule::in(['7', '30', '90'])],
        ]);

        $days = (int) ($validated['period'] ?? 30);
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $shipments = Shipment::query()
            ->with(['order:id,order_number,grand_total,status,customer_name,customer_phone'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        return response()->json([
            'data' => [
                'period' => [
                    'days' => $days,
                    'start' => $start->toISOString(),
                    'end' => $end->toISOString(),
                ],
                'summary' => $this->summary($shipments),
                'daily_fulfillment' => $this->dailyFulfillment($start, $end),
                'courier_performance' => $this->courierPerformance($shipments),
                'district_load' => $this->districtLoad($shipments),
                'attention_queue' => $this->attentionQueue(),
            ],
        ]);
    }

    /**
     * @param  Collection<int, Shipment>  $shipments
     * @return array<string, int|float>
     */
    private function summary($shipments): array
    {
        $total = $shipments->count();
        $delivered = $shipments->where('status', ShipmentStatus::Delivered)->count();
        $failed = $shipments->where('status', ShipmentStatus::Failed)->count();
        $returnPickups = $shipments->where('direction', 'return_pickup')->count();
        $delayed = $shipments->filter(fn (Shipment $shipment): bool => $this->isDelayed($shipment))->count();
        $open = $shipments->filter(fn (Shipment $shipment): bool => $this->isOpen($shipment))->count();
        $codPending = $shipments
            ->filter(fn (Shipment $shipment): bool => $this->isOpen($shipment))
            ->sum('cod_amount');

        return [
            'shipments' => $total,
            'open_shipments' => $open,
            'delivered_shipments' => $delivered,
            'failed_shipments' => $failed,
            'delayed_shipments' => $delayed,
            'return_pickups' => $returnPickups,
            'cod_pending' => (int) $codPending,
            'delivery_rate_percent' => $total > 0 ? round(($delivered / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dailyFulfillment(Carbon $start, Carbon $end): array
    {
        $createdRows = Shipment::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->get()
            ->keyBy('date');

        $deliveredRows = Shipment::query()
            ->selectRaw('DATE(delivered_at) as date, COUNT(*) as total')
            ->whereBetween('delivered_at', [$start, $end])
            ->groupByRaw('DATE(delivered_at)')
            ->get()
            ->keyBy('date');

        $trend = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $key = $date->toDateString();
            $trend[] = [
                'date' => $key,
                'created' => (int) ($createdRows->get($key)->total ?? 0),
                'delivered' => (int) ($deliveredRows->get($key)->total ?? 0),
            ];
        }

        return $trend;
    }

    /**
     * @param  Collection<int, Shipment>  $shipments
     * @return array<int, array<string, mixed>>
     */
    private function courierPerformance($shipments): array
    {
        return $shipments
            ->groupBy(fn (Shipment $shipment): string => $shipment->courier_name ?: 'Unassigned')
            ->map(fn ($items, string $courier): array => [
                'courier' => $courier,
                'total' => $items->count(),
                'delivered' => $items->where('status', ShipmentStatus::Delivered)->count(),
                'failed' => $items->where('status', ShipmentStatus::Failed)->count(),
                'delayed' => $items->filter(fn (Shipment $shipment): bool => $this->isDelayed($shipment))->count(),
                'cod_amount' => (int) $items->sum('cod_amount'),
            ])
            ->sortByDesc('total')
            ->values()
            ->take(8)
            ->all();
    }

    /**
     * @param  Collection<int, Shipment>  $shipments
     * @return array<int, array<string, mixed>>
     */
    private function districtLoad($shipments): array
    {
        return $shipments
            ->groupBy('recipient_district')
            ->map(fn ($items, string $district): array => [
                'district' => $district,
                'total' => $items->count(),
                'open' => $items->filter(fn (Shipment $shipment): bool => $this->isOpen($shipment))->count(),
                'delayed' => $items->filter(fn (Shipment $shipment): bool => $this->isDelayed($shipment))->count(),
            ])
            ->sortByDesc('open')
            ->values()
            ->take(8)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function attentionQueue(): array
    {
        return Shipment::query()
            ->with('order:id,order_number,grand_total,status,customer_name,customer_phone')
            ->where(function ($query): void {
                $query->where('status', ShipmentStatus::Failed->value)
                    ->orWhere(function ($query): void {
                        $query->whereNotIn('status', $this->closedStatuses())
                            ->whereNotNull('estimated_delivery_at')
                            ->where('estimated_delivery_at', '<', now());
                    })
                    ->orWhere(function ($query): void {
                        $query->whereNotIn('status', $this->closedStatuses())
                            ->whereNull('tracking_number')
                            ->where('created_at', '<=', now()->subDay());
                    });
            })
            ->latest('estimated_delivery_at')
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Shipment $shipment): array => [
                'id' => $shipment->id,
                'order_number' => $shipment->order?->order_number,
                'direction' => $shipment->direction,
                'status' => $shipment->status?->value ?? $shipment->status,
                'courier_name' => $shipment->courier_name,
                'tracking_number' => $shipment->tracking_number,
                'recipient_name' => $shipment->recipient_name,
                'recipient_phone' => $shipment->recipient_phone,
                'recipient_district' => $shipment->recipient_district,
                'estimated_delivery_at' => $shipment->estimated_delivery_at?->toISOString(),
                'reason' => $this->attentionReason($shipment),
            ])
            ->values()
            ->all();
    }

    private function attentionReason(Shipment $shipment): string
    {
        if ($shipment->status === ShipmentStatus::Failed) {
            return 'failed_delivery';
        }

        if ($this->isDelayed($shipment)) {
            return 'delivery_delayed';
        }

        return 'tracking_missing';
    }

    private function isDelayed(Shipment $shipment): bool
    {
        return $this->isOpen($shipment)
            && $shipment->estimated_delivery_at !== null
            && $shipment->estimated_delivery_at->isPast();
    }

    private function isOpen(Shipment $shipment): bool
    {
        return ! in_array($shipment->status?->value ?? $shipment->status, $this->closedStatuses(), true);
    }

    /**
     * @return array<int, string>
     */
    private function closedStatuses(): array
    {
        return [
            ShipmentStatus::Delivered->value,
            ShipmentStatus::Returned->value,
            ShipmentStatus::Cancelled->value,
        ];
    }
}
