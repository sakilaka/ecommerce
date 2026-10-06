<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminNewsletterLeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone,
            'source' => $this->source,
            'locale' => $this->locale,
            'interest' => $this->interest,
            'consented_at' => $this->consented_at?->toISOString(),
            'last_subscribed_at' => $this->last_subscribed_at?->toISOString(),
            'is_active' => $this->is_active,
            'admin_note' => $this->admin_note,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
