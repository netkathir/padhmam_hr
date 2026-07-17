<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;

class FoundationBranchIsolationTest extends FoundationTestCase
{
    public function test_branch_user_only_sees_records_from_assigned_branch(): void
    {
        $otherUser = $this->createUserInBranch($this->factoryUnit, [
            'name' => 'Factory User',
            'username' => 'factory.user',
            'email' => 'factory.user@padmamindustries.test',
        ]);

        $this->loginAsSeededBranchAdmin();

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSee($this->branchAdmin->name)
            ->assertDontSee($otherUser->name);
    }

    public function test_branch_user_cannot_access_another_branch_record_by_changing_the_url(): void
    {
        $otherUser = $this->createUserInBranch($this->factoryUnit, [
            'name' => 'Factory User',
            'username' => 'factory.user',
            'email' => 'factory.user@padmamindustries.test',
        ]);

        $this->loginAsSeededBranchAdmin();

        $this->get(route('users.show', $otherUser))->assertForbidden();
    }

    public function test_branch_user_cannot_submit_another_branch_id(): void
    {
        $this->loginAsSeededBranchAdmin();
        $roleId = \App\Models\Role::query()->where('slug', 'branch-administrator')->firstOrFail()->id;

        $this->post(route('users.store'), [
            'name' => 'Tampered User',
            'username' => 'tampered.user',
            'email' => 'tampered.user@padmamindustries.test',
            'phone' => null,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'roles' => [$roleId],
            'branch_id' => $this->factoryUnit->id,
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'tampered.user@padmamindustries.test',
            'branch_id' => $this->headOffice->id,
        ]);
    }

    public function test_branch_user_cannot_switch_branch_context(): void
    {
        $this->loginAsSeededBranchAdmin();

        $this->post(route('branch-context.update'), [
            'branch_selection' => $this->factoryUnit->id,
        ])->assertForbidden();
    }

    public function test_super_administrator_can_switch_to_an_active_branch(): void
    {
        $this->loginAsSeededSuperAdmin();

        $this->post(route('branch-context.update'), [
            'branch_selection' => $this->factoryUnit->id,
        ])->assertSessionHas('hrms.active_branch.id', $this->factoryUnit->id);
    }

    public function test_super_administrator_cannot_switch_to_an_inactive_branch(): void
    {
        $inactiveBranch = Branch::query()->create([
            'organization_id' => $this->headOffice->organization_id,
            'branch_code' => 'BR-INV',
            'branch_name' => 'Inactive Branch',
            'branch_type' => Branch::TYPE_OFFICE,
            'address_line_1' => 'Test Address',
            'city' => 'Chennai',
            'state' => 'Tamil Nadu',
            'country' => 'India',
            'postal_code' => '600001',
            'timezone' => 'Asia/Kolkata',
            'status' => 'inactive',
        ]);

        $this->loginAsSeededSuperAdmin();

        $this->post(route('branch-context.update'), [
            'branch_selection' => $inactiveBranch->id,
        ])->assertNotFound();
    }

    public function test_write_operation_is_blocked_when_all_branches_is_selected(): void
    {
        $this->loginAsSeededSuperAdmin();

        $this->post(route('users.store'), [
            'name' => 'All Branches User',
            'username' => 'all-branches.user',
            'email' => 'all.branches.user@padmamindustries.test',
            'phone' => null,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'roles' => [\App\Models\Role::query()->where('slug', 'branch-administrator')->firstOrFail()->id],
        ])->assertStatus(422);

        $this->assertDatabaseMissing('users', [
            'email' => 'all.branches.user@padmamindustries.test',
        ]);
    }

    public function test_cross_branch_related_records_are_rejected(): void
    {
        $this->loginAsSeededBranchAdmin();
        $factoryUser = $this->createUserInBranch($this->factoryUnit, [
            'name' => 'Factory User',
            'username' => 'factory.user',
            'email' => 'factory.user@padmamindustries.test',
        ]);

        $this->put(route('users.update', $factoryUser), [
            'name' => 'Factory User',
            'username' => 'factory.user',
            'email' => 'factory.user@padmamindustries.test',
            'phone' => null,
            'password' => '',
            'password_confirmation' => '',
            'status' => 'active',
            'roles' => [\App\Models\Role::query()->where('slug', 'branch-administrator')->firstOrFail()->id],
            'branch_id' => $this->headOffice->id,
        ])->assertForbidden();
    }
}
