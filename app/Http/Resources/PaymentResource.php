<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'method' => $this->method?->value ?? $this->method,
            'status' => $this->status?->value ?? $this->status,
            'amount' => $this->amount,
            'gateway' => $this->gateway,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'gateway_payload' => $this->publicGatewayPayload(),
            'manual_summary' => $this->manualSummary(),
            'paid_at' => $this->paid_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function publicGatewayPayload(): ?array
    {
        $payload = $this->gateway_payload;

        if (! is_array($payload)) {
            return null;
        }

        if (isset($payload['manual_submission']) && is_array($payload['manual_submission'])) {
            $payload['manual_submission'] = collect($payload['manual_submission'])
                ->only(['method', 'sender_phone', 'transaction_id', 'note', 'status', 'submitted_at', 'reviewed_at'])
                ->all();
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function manualSummary(): ?array
    {
        $payload = $this->gateway_payload ?? [];
        $submission = $payload['manual_submission'] ?? null;

        if (! is_array($submission)) {
            return null;
        }

        return [
            'status' => $submission['status'] ?? null,
            'sender_phone' => $submission['sender_phone'] ?? null,
            'transaction_id' => $submission['transaction_id'] ?? null,
            'submitted_at' => $submission['submitted_at'] ?? null,
            'reviewed_at' => $submission['reviewed_at'] ?? null,
        ];
    }
}
