<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminDeliveryZoneResource;
use App\Models\AuditLog;
use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class DeliveryZoneController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'cod' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $zones = DeliveryZone::query()
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('district', 'like', "%{$search}%")
                        ->orWhere('area', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('active', $validated), fn (Builder $query): Builder => $query->where('is_active', $request->boolean('active')))
            ->when(array_key_exists('cod', $validated), fn (Builder $query): Builder => $query->where('cod_available', $request->boolean('cod')))
            ->orderBy('district')
            ->orderByRaw('area is null')
            ->orderBy('area')
            ->paginate($perPage);

        return AdminDeliveryZoneResource::collection($zones);
    }

    public function store(Request $request): AdminDeliveryZoneResource
    {
        $zone = DeliveryZone::query()->create($this->validatedData($request));

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'delivery_zone.created',
            'auditable_type' => DeliveryZone::class,
            'auditable_id' => $zone->id,
            'new_values' => $zone->only(['district', 'area', 'delivery_charge', 'cod_charge', 'estimated_min_days', 'estimated_max_days', 'cod_available', 'is_active']),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return AdminDeliveryZoneResource::make($zone);
    }

    public function update(Request $request, DeliveryZone $deliveryZone): AdminDeliveryZoneResource
    {
        $before = $deliveryZone->only(['district', 'area', 'delivery_charge', 'cod_charge', 'estimated_min_days', 'estimated_max_days', 'cod_available', 'is_active']);

        $deliveryZone->update($this->validatedData($request, $deliveryZone));

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => 'delivery_zone.updated',
            'auditable_type' => DeliveryZone::class,
            'auditable_id' => $deliveryZone->id,
            'old_values' => $before,
            'new_values' => $deliveryZone->only(['district', 'area', 'delivery_charge', 'cod_charge', 'estimated_min_days', 'estimated_max_days', 'cod_available', 'is_active']),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return AdminDeliveryZoneResource::make($deliveryZone->refresh());
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?DeliveryZone $deliveryZone = null): array
    {
        return $request->validate([
            'district' => ['required', 'string', 'max:80'],
            'area' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('delivery_zones', 'area')
                    ->where(fn ($query) => $query->where('district', $request->string('district')->toString()))
                    ->ignore($deliveryZone),
            ],
            'delivery_charge' => ['required', 'integer', 'min:0', 'max:10000000'],
            'cod_charge' => ['required', 'integer', 'min:0', 'max:10000000'],
            'estimated_min_days' => ['required', 'integer', 'min:0', 'max:30'],
            'estimated_max_days' => ['required', 'integer', 'min:0', 'max:30', 'gte:estimated_min_days'],
            'cod_available' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
