<?php

namespace App\Http\Resources\Admin;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentRefund;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPaymentResource extends JsonResource
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
            'gateway_payload' => $this->gateway_payload,
            'result_url' => $this->gateway_transaction_id ? url('/payment/result?transaction_id='.$this->gateway_transaction_id) : null,
            'gateway_summary' => $this->gatewaySummary(),
            'actions' => $this->availableActions(),
            'refund_summary' => $this->refundSummary(),
            'refunds' => $this->refunds(),
            'timeline' => $this->when($request->routeIs('api.admin.payments.show'), fn (): array => $this->timeline()),
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'order' => $this->whenLoaded('order', fn (): ?array => $this->order ? [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status?->value ?? $this->order->status,
                'payment_status' => $this->order->payment_status?->value ?? $this->order->payment_status,
                'payment_method' => $this->order->payment_method?->value ?? $this->order->payment_method,
                'customer_name' => $this->order->customer_name,
                'customer_email' => $this->order->customer_email,
                'customer_phone' => $this->order->customer_phone,
                'shipping_district' => $this->order->shipping_district,
                'shipping_area' => $this->order->shipping_area,
                'grand_total' => $this->order->grand_total,
                'created_at' => $this->order->created_at?->toISOString(),
            ] : null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function gatewaySummary(): array
    {
        $payload = $this->gateway_payload ?? [];
        $sslCommerz = $payload['sslcommerz'] ?? [];
        $callback = $payload['sslcommerz_callback'] ?? [];

        return [
            'sessionkey' => $sslCommerz['sessionkey'] ?? null,
            'redirect_url' => $sslCommerz['redirect_url'] ?? null,
            'callback_event' => $callback['event'] ?? null,
            'validation_required' => (bool) ($callback['validation_required'] ?? false),
            'validation_status' => $callback['validation']['status'] ?? null,
            'bank_transaction_id' => $callback['validation']['bank_tran_id'] ?? null,
            'manual_transaction_id' => $payload['manual_submission']['transaction_id'] ?? null,
            'manual_sender_phone' => $payload['manual_submission']['sender_phone'] ?? null,
            'manual_status' => $payload['manual_submission']['status'] ?? null,
            'risk_message' => $callback['error'] ?? null,
            'received_at' => $callback['received_at'] ?? ($payload['manual_submission']['submitted_at'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function refundSummary(): array
    {
        $amount = (int) $this->amount;
        $refunded = (int) ($this->refunds_sum_amount ?? $this->refunds?->sum('amount') ?? 0);

        return [
            'refunded_amount' => $refunded,
            'remaining_amount' => max(0, $amount - $refunded),
            'is_fully_refunded' => $amount > 0 && $refunded >= $amount,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function refunds(): array
    {
        if (! $this->relationLoaded('refunds')) {
            return [];
        }

        return $this->refunds->map(fn ($refund): array => [
            'id' => $refund->id,
            'amount' => $refund->amount,
            'method' => $refund->method,
            'status' => $refund->status,
            'reference' => $refund->reference,
            'reason' => $refund->reason,
            'admin_note' => $refund->admin_note,
            'refunded_at' => $refund->refunded_at?->toISOString(),
            'created_at' => $refund->created_at?->toISOString(),
        ])->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function timeline(): array
    {
        $events = collect([
            [
                'type' => 'payment.created',
                'label' => 'Payment created',
                'label_bn' => 'পেমেন্ট তৈরি হয়েছে',
                'description' => 'Payment record was created for this order.',
                'description_bn' => 'এই অর্ডারের জন্য পেমেন্ট রেকর্ড তৈরি হয়েছে।',
                'actor' => null,
                'amount' => $this->amount,
                'status' => $this->status?->value ?? $this->status,
                'created_at' => $this->created_at?->toISOString(),
            ],
        ]);

        $auditEvents = AuditLog::query()
            ->with('user:id,name,email,phone')
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where('auditable_type', Payment::class)
                        ->where('auditable_id', $this->id);
                })
                    ->orWhere(function ($query): void {
                        $query->where('auditable_type', PaymentRefund::class)
                            ->whereIn('auditable_id', PaymentRefund::query()->where('payment_id', $this->id)->pluck('id'));
                    });
            })
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->map(fn (AuditLog $log): array => $this->timelineAuditItem($log));

        return $events
            ->merge($auditEvents)
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function timelineAuditItem(AuditLog $log): array
    {
        return [
            'type' => $log->event,
            'label' => $this->timelineLabel($log->event),
            'label_bn' => $this->timelineLabelBn($log->event),
            'description' => $this->timelineDescription($log),
            'description_bn' => $this->timelineDescriptionBn($log),
            'actor' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
                'email' => $log->user->email,
                'phone' => $log->user->phone,
            ] : null,
            'amount' => $log->new_values['amount'] ?? null,
            'status' => $log->new_values['status'] ?? ($log->new_values['payment_status'] ?? null),
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at?->toISOString(),
        ];
    }

    private function timelineLabel(string $event): string
    {
        return match ($event) {
            'payment.updated' => 'Payment reconciled',
            'payment.refund.recorded' => 'Refund recorded',
            'payment.manual-proof.approved' => 'Manual proof approved',
            'payment.manual-proof.rejected' => 'Manual proof rejected',
            default => str($event)->replace('.', ' ')->title()->toString(),
        };
    }

    private function timelineLabelBn(string $event): string
    {
        return match ($event) {
            'payment.updated' => 'পেমেন্ট রিকনসাইল হয়েছে',
            'payment.refund.recorded' => 'রিফান্ড রেকর্ড হয়েছে',
            'payment.manual-proof.approved' => 'ম্যানুয়াল প্রুফ অনুমোদিত',
            'payment.manual-proof.rejected' => 'ম্যানুয়াল প্রুফ বাতিল',
            default => $this->timelineLabel($event),
        };
    }

    private function timelineDescription(AuditLog $log): string
    {
        return match ($log->event) {
            'payment.updated' => 'Status changed from '.($log->old_values['status'] ?? '-').' to '.($log->new_values['status'] ?? '-').'.',
            'payment.refund.recorded' => 'Refund '.($log->new_values['reference'] ?? 'without reference').' was recorded.',
            'payment.manual-proof.approved' => 'Customer payment proof was approved after review.',
            'payment.manual-proof.rejected' => 'Customer payment proof was rejected after review.',
            default => 'Payment activity was recorded.',
        };
    }

    private function timelineDescriptionBn(AuditLog $log): string
    {
        return match ($log->event) {
            'payment.updated' => 'স্ট্যাটাস '.($log->old_values['status'] ?? '-').' থেকে '.($log->new_values['status'] ?? '-').' করা হয়েছে।',
            'payment.refund.recorded' => 'রিফান্ড '.($log->new_values['reference'] ?? 'reference ছাড়া').' রেকর্ড হয়েছে।',
            'payment.manual-proof.approved' => 'রিভিউ করার পর customer payment proof approve করা হয়েছে।',
            'payment.manual-proof.rejected' => 'রিভিউ করার পর customer payment proof reject করা হয়েছে।',
            default => 'Payment activity রেকর্ড হয়েছে।',
        };
    }

    /**
     * @return array<string, bool>
     */
    private function availableActions(): array
    {
        $status = $this->status?->value ?? $this->status;

        return [
            'can_mark_paid' => in_array($status, ['pending', 'unpaid'], true),
            'can_mark_failed' => in_array($status, ['pending', 'unpaid'], true),
            'can_refund' => $status === 'paid' && $this->refundSummary()['remaining_amount'] > 0,
            'can_verify_manual_proof' => ($this->gateway_payload['manual_submission']['status'] ?? null) === 'submitted_for_review',
        ];
    }
}
