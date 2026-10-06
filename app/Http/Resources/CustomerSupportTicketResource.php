<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerSupportTicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'topic' => $this->topic,
            'priority' => $this->priority,
            'status' => $this->status,
            'subject' => $this->subject,
            'message' => $this->message,
            'admin_reply' => $this->admin_reply,
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'order' => $this->whenLoaded('order', fn (): ?array => $this->order ? [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status?->value ?? $this->order->status,
                'grand_total' => $this->order->grand_total,
                'created_at' => $this->order->created_at?->toISOString(),
            ] : null),
        ];
    }
}
