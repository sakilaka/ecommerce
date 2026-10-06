<?php

namespace Tests\Feature;

use App\Enums\AdminPermission;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_permission_protected_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/admin/dashboard')->assertOk();
        $this->actingAs($admin)->getJson('/api/admin/reports')->assertOk();
    }

    public function test_limited_admin_can_access_only_allowed_module_routes(): void
    {
        $admin = User::factory()->adminWithPermissions([
            AdminPermission::ReportsView->value,
            AdminPermission::DashboardView->value,
        ])->create();

        $this->actingAs($admin)->getJson('/api/admin/reports')->assertOk();
        $this->actingAs($admin)->getJson('/api/admin/orders')->assertForbidden();
        $this->actingAs($admin)->getJson('/api/admin/products')->assertForbidden();
    }

    public function test_inactive_admin_cannot_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => false]);

        $this->actingAs($admin)->getJson('/api/admin/health')->assertForbidden();
        $this->actingAs($admin)->getJson('/api/admin/reports')->assertForbidden();
    }

    public function test_staff_manager_can_create_limited_admin_staff(): void
    {
        $manager = User::factory()->adminWithPermissions([AdminPermission::StaffManage->value])->create();

        $response = $this->actingAs($manager)->postJson('/api/admin/staff', [
            'name' => 'Order Manager',
            'email' => 'orders@example.com',
            'phone' => '01922222222',
            'password' => 'Secret12345',
            'is_active' => true,
            'permissions' => [AdminPermission::OrdersManage->value, AdminPermission::DashboardView->value],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'orders@example.com')
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.permissions.0', 'orders.manage')
            ->assertJsonPath('data.permissions.1', 'dashboard.view');

        $this->assertDatabaseHas('users', [
            'email' => 'orders@example.com',
            'role' => UserRole::Admin->value,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'event' => 'admin_staff.created',
            'auditable_type' => User::class,
        ]);
    }

    public function test_staff_manager_can_update_admin_permissions(): void
    {
        $manager = User::factory()->adminWithPermissions([AdminPermission::StaffManage->value])->create();
        $staff = User::factory()->adminWithPermissions([AdminPermission::OrdersManage->value])->create(['email' => 'staff@example.com']);

        $response = $this->actingAs($manager)->patchJson('/api/admin/staff/'.$staff->id, [
            'name' => 'Catalog Manager',
            'email' => 'catalog@example.com',
            'phone' => null,
            'is_active' => false,
            'permissions' => [AdminPermission::CatalogManage->value],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Catalog Manager')
            ->assertJsonPath('data.email', 'catalog@example.com')
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.permissions.0', 'catalog.manage');

        $staff->refresh();
        $this->assertSame([AdminPermission::CatalogManage->value], $staff->permissions);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'event' => 'admin_staff.updated',
            'auditable_type' => User::class,
            'auditable_id' => $staff->id,
        ]);
        $this->assertSame([AdminPermission::CatalogManage->value], AuditLog::query()->where('event', 'admin_staff.updated')->first()?->new_values['permissions']);
    }

    public function test_staff_management_rejects_invalid_payload(): void
    {
        $manager = User::factory()->adminWithPermissions([AdminPermission::StaffManage->value])->create();
        User::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->actingAs($manager)->postJson('/api/admin/staff', [
            'name' => '',
            'email' => 'duplicate@example.com',
            'password' => 'short',
            'is_active' => 'sometimes',
            'permissions' => ['orders.delete'],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'is_active', 'permissions.0']);
    }

    public function test_admin_without_staff_permission_cannot_manage_staff(): void
    {
        $admin = User::factory()->adminWithPermissions([AdminPermission::OrdersManage->value])->create();

        $this->actingAs($admin)->getJson('/api/admin/staff')->assertForbidden();
        $this->actingAs($admin)->postJson('/api/admin/staff', [])->assertForbidden();
    }
}
