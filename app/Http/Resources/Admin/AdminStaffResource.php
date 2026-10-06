<?php

namespace App\Http\Resources\Admin;

use App\Enums\AdminPermission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminStaffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value ?? $this->role,
            'is_active' => $this->is_active,
            'permissions' => $this->permissions ?? ['*'],
            'available_permissions' => AdminPermission::values(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
