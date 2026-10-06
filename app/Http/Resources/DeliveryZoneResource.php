<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryZoneResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'district' => $this->district,
            'area' => $this->area,
            'delivery_charge' => $this->delivery_charge,
            'cod_charge' => $this->cod_charge,
            'estimated_min_days' => $this->estimated_min_days,
            'estimated_max_days' => $this->estimated_max_days,
            'cod_available' => $this->cod_available,
        ];
    }
}
