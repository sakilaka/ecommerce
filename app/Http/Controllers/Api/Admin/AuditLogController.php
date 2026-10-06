<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminAuditLogResource;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', 'string', 'max:80'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $logs = AuditLog::query()
            ->with('user:id,name,email,phone')
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('event', 'like', "%{$search}%")
                        ->orWhere('auditable_type', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when(isset($validated['event']), fn (Builder $query): Builder => $query->where('event', $validated['event']))
            ->when(isset($validated['user_id']), fn (Builder $query): Builder => $query->where('user_id', $validated['user_id']))
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);

        return AdminAuditLogResource::collection($logs);
    }
}
