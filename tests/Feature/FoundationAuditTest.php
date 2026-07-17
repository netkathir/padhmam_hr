<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;

class FoundationAuditTest extends FoundationTestCase
{
    public function test_login_is_audited(): void
    {
        $this->post(route('login.store'), [
            'login' => config('hrms.branch_admin.email'),
            'password' => config('hrms.branch_admin.password'),
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'login',
            'module' => 'authentication',
            'user_id' => $this->branchAdmin->id,
        ]);
    }

    public function test_branch_switch_is_audited(): void
    {
        $this->loginAsSeededSuperAdmin();

        $this->post(route('branch-context.update'), [
            'branch_selection' => $this->factoryUnit->id,
        ])->assertSessionHas('hrms.active_branch.id', $this->factoryUnit->id);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'branch_switch',
            'module' => 'branch-context',
            'user_id' => $this->superAdmin->id,
        ]);
    }

    public function test_administrative_updates_are_audited(): void
    {
        $this->loginAsSeededSuperAdmin();

        $this->put(route('branches.update', $this->headOffice), [
            'branch_code' => $this->headOffice->branch_code,
            'branch_name' => 'Head Office Updated',
            'branch_type' => $this->headOffice->branch_type,
            'address_line_1' => $this->headOffice->address_line_1,
            'address_line_2' => $this->headOffice->address_line_2,
            'city' => $this->headOffice->city,
            'state' => $this->headOffice->state,
            'country' => $this->headOffice->country,
            'postal_code' => $this->headOffice->postal_code,
            'phone' => $this->headOffice->phone,
            'email' => $this->headOffice->email,
            'timezone' => $this->headOffice->timezone,
        ])->assertRedirect(route('branches.show', $this->headOffice));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'branch_update',
            'module' => 'branch',
            'user_id' => $this->superAdmin->id,
        ]);
    }

    public function test_passwords_and_sensitive_values_are_not_stored_in_audit_logs(): void
    {
        $this->loginAsSeededBranchAdmin();

        $this->put(route('password.change.update'), [
            'current_password' => config('hrms.branch_admin.password'),
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertSessionHas('status');

        /** @var AuditLog $auditLog */
        $auditLog = AuditLog::query()->where('event', 'password_change')->latest('id')->firstOrFail();

        $this->assertArrayNotHasKey('password', $auditLog->new_values);
        $this->assertArrayNotHasKey('current_password', $auditLog->new_values);
        $this->assertArrayNotHasKey('remember_token', $auditLog->new_values);
        $this->assertArrayNotHasKey('password', $auditLog->old_values);
    }
}
