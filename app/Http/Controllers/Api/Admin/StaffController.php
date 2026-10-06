<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\AdminPermission;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminStaffResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $staff = User::query()
            ->where('role', UserRole::Admin->value)
            ->when(isset($validated['q']), function (Builder $query) use ($validated): void {
                $search = $validated['q'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('active', $validated), fn (Builder $query): Builder => $query->where('is_active', $request->boolean('active')))
            ->latest('created_at')
            ->paginate((int) ($validated['per_page'] ?? 15));

        return AdminStaffResource::collection($staff);
    }

    public function store(Request $request): AdminStaffResource
    {
        $validated = $request->validate($this->rules());

        $staff = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Admin,
            'is_active' => $validated['is_active'],
            'permissions' => $this->normalizedPermissions($validated['permissions']),
        ]);

        $this->audit($request, 'admin_staff.created', $staff, [], $this->auditValues($staff));

        return AdminStaffResource::make($staff);
    }

    public function update(Request $request, User $staff): AdminStaffResource
    {
        abort_unless($staff->role === UserRole::Admin, 404);

        $validated = $request->validate($this->rules($staff));
        $before = $this->auditValues($staff);

        $staff->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'],
            'permissions' => $this->normalizedPermissions($validated['permissions']),
        ] + (isset($validated['password']) ? ['password' => Hash::make($validated['password'])] : []));

        $this->audit($request, 'admin_staff.updated', $staff, $before, $this->auditValues($staff));

        return AdminStaffResource::make($staff->refresh());
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?User $staff = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($staff)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($staff)],
            'password' => [$staff ? 'nullable' : 'required', 'string', 'min:8', 'max:120'],
            'is_active' => ['required', 'boolean'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', Rule::in(array_merge(['*'], AdminPermission::values()))],
        ];
    }

    /**
     * @param  list<string>  $permissions
     * @return list<string>
     */
    private function normalizedPermissions(array $permissions): array
    {
        if (in_array('*', $permissions, true)) {
            return ['*'];
        }

        return array_values(array_unique($permissions));
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(User $staff): array
    {
        return [
            'name' => $staff->name,
            'email' => $staff->email,
            'phone' => $staff->phone,
            'is_active' => $staff->is_active,
            'permissions' => $staff->permissions ?? ['*'],
        ];
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function audit(Request $request, string $event, User $staff, array $oldValues, array $newValues): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => $event,
            'auditable_type' => User::class,
            'auditable_id' => $staff->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }
}
