<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminNewsletterLeadResource;
use App\Models\NewsletterLead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class NewsletterLeadController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'source' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $leads = NewsletterLead::query()
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('interest', 'like', "%{$search}%");
                });
            })
            ->when(isset($validated['source']), fn (Builder $query): Builder => $query->where('source', $validated['source']))
            ->when(($validated['status'] ?? null) === 'active', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when(($validated['status'] ?? null) === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->latest('created_at')
            ->paginate((int) ($validated['per_page'] ?? 12));

        return AdminNewsletterLeadResource::collection($leads);
    }

    public function update(Request $request, NewsletterLead $newsletterLead): AdminNewsletterLeadResource
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $newsletterLead->update($validated);

        return AdminNewsletterLeadResource::make($newsletterLead->refresh());
    }
}
