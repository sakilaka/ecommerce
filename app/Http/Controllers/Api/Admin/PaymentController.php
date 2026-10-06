<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminPaymentResource;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentRefund;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $this->validatedFilters($request, true);

        $payments = $this->filteredPayments($validated)
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return AdminPaymentResource::collection($payments);
    }

    public function export(Request $request): StreamedResponse
    {
        $validated = $this->validatedFilters($request);
        $filename = 'payments-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($validated): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'Payment ID',
                'Order Number',
                'Customer Name',
                'Customer Phone',
                'Customer Email',
                'Method',
                'Status',
                'Gateway',
                'Gateway Transaction ID',
                'Amount',
                'Refunded Amount',
                'Remaining Amount',
                'Paid At',
                'Created At',
            ]);

            $this->filteredPayments($validated)
                ->withSum('refunds', 'amount')
                ->latest('created_at')
                ->latest('id')
                ->lazy(500)
                ->each(function (Payment $payment) use ($handle): void {
                    $amount = (int) $payment->amount;
                    $refunded = (int) ($payment->refunds_sum_amount ?? 0);

                    fputcsv($handle, array_map($this->csvCell(...), [
                        $payment->id,
                        $payment->order?->order_number,
                        $payment->order?->customer_name,
                        $payment->order?->customer_phone,
                        $payment->order?->customer_email,
                        $payment->method?->value ?? $payment->method,
                        $payment->status?->value ?? $payment->status,
                        $payment->gateway,
                        $payment->gateway_transaction_id,
                        $amount,
                        $refunded,
                        max(0, $amount - $refunded),
                        $payment->paid_at?->toISOString(),
                        $payment->created_at?->toISOString(),
                    ]));
                });
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Payment $payment): AdminPaymentResource
    {
        return AdminPaymentResource::make($this->freshPayment($payment));
    }

    public function update(Request $request, Payment $payment): AdminPaymentResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(PaymentStatus::class)],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'amount' => ['required', 'integer', 'min:0', 'max:100000000'],
            'gateway' => ['nullable', 'string', 'max:80'],
            'gateway_transaction_id' => ['nullable', 'string', 'max:120'],
        ]);

        $payment = DB::transaction(function () use ($payment, $request, $validated): Payment {
            $payment = Payment::query()->with('order')->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $before = $this->auditValues($payment);
            $status = PaymentStatus::from($validated['status']);

            $payment->update([
                'status' => $status,
                'method' => $validated['method'],
                'amount' => $validated['amount'],
                'gateway' => $validated['gateway'] ?? null,
                'gateway_transaction_id' => $validated['gateway_transaction_id'] ?? null,
                'paid_at' => $status === PaymentStatus::Paid && ! $payment->paid_at ? now() : $payment->paid_at,
            ]);

            $payment->order?->update([
                'payment_status' => $status,
                'payment_method' => $validated['method'],
            ]);

            AuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'event' => 'payment.updated',
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->id,
                'old_values' => $before,
                'new_values' => $this->auditValues($payment),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return $payment;
        });

        return AdminPaymentResource::make($this->freshPayment($payment));
    }

    public function refund(Request $request, Payment $payment): AdminPaymentResource
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'method' => ['required', 'string', 'max:40', Rule::in(['manual', 'bkash', 'nagad', 'bank', 'card', 'sslcommerz'])],
            'reference' => ['nullable', 'string', 'max:120'],
            'reason' => ['required', 'string', 'max:500'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = DB::transaction(function () use ($payment, $request, $validated): Payment {
            $payment = Payment::query()
                ->with('order')
                ->withSum('refunds', 'amount')
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->status !== PaymentStatus::Paid) {
                throw ValidationException::withMessages([
                    'payment' => 'Only paid payments can be refunded.',
                ]);
            }

            $refundedAmount = (int) ($payment->refunds_sum_amount ?? 0);
            $remainingAmount = max(0, (int) $payment->amount - $refundedAmount);

            if ((int) $validated['amount'] > $remainingAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Refund amount cannot exceed the remaining paid amount.',
                ]);
            }

            $refund = PaymentRefund::query()->create([
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'user_id' => $request->user()?->id,
                'amount' => $validated['amount'],
                'method' => $validated['method'],
                'status' => 'recorded',
                'reference' => $validated['reference'] ?? null,
                'reason' => $validated['reason'],
                'admin_note' => $validated['admin_note'] ?? null,
                'refunded_at' => now(),
            ]);

            if ($refundedAmount + (int) $validated['amount'] >= (int) $payment->amount) {
                $payment->update(['status' => PaymentStatus::Refunded]);
                $payment->order?->update(['payment_status' => PaymentStatus::Refunded]);
            }

            AuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'event' => 'payment.refund.recorded',
                'auditable_type' => PaymentRefund::class,
                'auditable_id' => $refund->id,
                'old_values' => null,
                'new_values' => [
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'amount' => $validated['amount'],
                    'method' => $validated['method'],
                    'reference' => $validated['reference'] ?? null,
                    'reason' => $validated['reason'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return $payment;
        });

        return AdminPaymentResource::make($this->freshPayment($payment));
    }

    public function verifyManualProof(Request $request, Payment $payment): AdminPaymentResource
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = DB::transaction(function () use ($payment, $request, $validated): Payment {
            $payment = Payment::query()
                ->with('order')
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $payload = $payment->gateway_payload ?? [];
            $submission = $payload['manual_submission'] ?? null;

            if (! is_array($submission) || ($submission['status'] ?? null) !== 'submitted_for_review') {
                throw ValidationException::withMessages([
                    'payment' => 'This payment does not have submitted manual proof waiting for review.',
                ]);
            }

            $decision = $validated['decision'];
            $payload['manual_submission'] = array_merge($submission, [
                'status' => $decision === 'approved' ? 'verified' : 'rejected',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => now()->toISOString(),
                'admin_note' => $validated['admin_note'] ?? null,
            ]);

            $updates = ['gateway_payload' => $payload];

            if ($decision === 'approved') {
                $updates['status'] = PaymentStatus::Paid;
                $updates['paid_at'] = $payment->paid_at ?? now();
            }

            $payment->update($updates);

            if ($decision === 'approved') {
                $payment->order?->update([
                    'payment_status' => PaymentStatus::Paid,
                    'payment_method' => $payment->method,
                ]);
            }

            AuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'event' => 'payment.manual-proof.'.($decision === 'approved' ? 'approved' : 'rejected'),
                'auditable_type' => Payment::class,
                'auditable_id' => $payment->id,
                'old_values' => ['manual_submission' => $submission],
                'new_values' => [
                    'manual_submission' => $payload['manual_submission'],
                    'payment_status' => $payment->status?->value ?? $payment->status,
                    'order_payment_status' => $payment->order?->payment_status?->value ?? $payment->order?->payment_status,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            return $payment;
        });

        return AdminPaymentResource::make($this->freshPayment($payment));
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFilters(Request $request, bool $withPagination = false): array
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'gateway' => ['nullable', 'string', 'max:80'],
        ];

        if ($withPagination) {
            $rules['per_page'] = ['nullable', 'integer', 'min:1', 'max:50'];
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return Builder<Payment>
     */
    private function filteredPayments(array $validated): Builder
    {
        return Payment::query()
            ->with('order:id,order_number,status,payment_status,payment_method,customer_name,customer_email,customer_phone,shipping_district,shipping_area,grand_total,created_at')
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->where('status', $validated['status']))
            ->when(isset($validated['method']), fn (Builder $query): Builder => $query->where('method', $validated['method']))
            ->when(isset($validated['gateway']), fn (Builder $query): Builder => $query->where('gateway', $validated['gateway']))
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('gateway_transaction_id', 'like', "%{$search}%")
                        ->orWhere('gateway', 'like', "%{$search}%")
                        ->orWhereHas('order', fn (Builder $query): Builder => $query->where('order_number', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%")->orWhere('customer_phone', 'like', "%{$search}%")->orWhere('customer_email', 'like', "%{$search}%"));
                });
            });
    }

    private function csvCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }

    private function freshPayment(Payment $payment): Payment
    {
        return $payment->refresh()
            ->load('order:id,order_number,status,payment_status,payment_method,customer_name,customer_email,customer_phone,shipping_district,shipping_area,grand_total,created_at')
            ->load(['refunds' => fn ($query) => $query->latest('created_at')->latest('id')])
            ->loadSum('refunds', 'amount');
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Payment $payment): array
    {
        return [
            'status' => $payment->status?->value ?? $payment->status,
            'method' => $payment->method?->value ?? $payment->method,
            'amount' => $payment->amount,
            'gateway' => $payment->gateway,
            'gateway_transaction_id' => $payment->gateway_transaction_id,
            'paid_at' => $payment->paid_at?->toISOString(),
        ];
    }
}
