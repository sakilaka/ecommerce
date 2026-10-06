<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['district', 'area', 'delivery_charge', 'cod_charge', 'estimated_min_days', 'estimated_max_days', 'cod_available', 'is_active'])]
class DeliveryZone extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_charge' => 'integer',
            'cod_charge' => 'integer',
            'estimated_min_days' => 'integer',
            'estimated_max_days' => 'integer',
            'cod_available' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
