<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'name' => $this->name,
            'phone' => $this->phone,
            'district' => $this->district,
            'area' => $this->area,
            'postal_code' => $this->postal_code,
            'address_line' => $this->address_line,
            'is_default' => $this->is_default,
        ];
    }
}
