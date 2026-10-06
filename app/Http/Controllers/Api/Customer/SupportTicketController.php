<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Http\Resources\CustomerSupportTicketResource;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class SupportTicketController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CustomerSupportTicketResource::collection(
            SupportTicket::query()
                ->with('order:id,order_number,status,grand_total,created_at')
                ->where('user_id', request()->user()->id)
                ->latest()
                ->limit(20)
                ->get(),
        );
    }

    public function store(StoreSupportTicketRequest $request): CustomerSupportTicketResource|JsonResponse
    {
        $validated = $request->validated();

        $ticket = SupportTicket::query()->create([
            'user_id' => $request->user()->id,
            'order_id' => $validated['order_id'] ?? null,
            'ticket_number' => $this->nextTicketNumber(),
            'topic' => $validated['topic'],
            'priority' => $validated['priority'] ?? 'normal',
            'status' => 'open',
            'subject' => $validated['subject'],
            'message' => $validated['message'],
        ]);

        return CustomerSupportTicketResource::make($ticket->load('order'))
            ->response()
            ->setStatusCode(201);
    }

    private function nextTicketNumber(): string
    {
        return 'SUP-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
    }
}
