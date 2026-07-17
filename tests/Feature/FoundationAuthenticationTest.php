<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;

class FoundationAuthenticationTest extends FoundationTestCase
{
    public function test_active_user_can_log_in(): void
    {
        $this->post(route('login.store'), [
            'login' => config('hrms.branch_admin.email'),
            'password' => config('hrms.branch_admin.password'),
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->post(route('login.store'), [
            'login' => config('hrms.branch_admin.email'),
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = $this->createInactiveUserInBranch($this->headOffice);

        $this->post(route('login.store'), [
            'login' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_user_assigned_to_an_inactive_branch_cannot_log_in(): void
    {
        $inactiveBranch = Branch::query()->create([
            'organization_id' => $this->headOffice->organization_id,
            'branch_code' => 'IN',
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

        $this->createUserInBranch($inactiveBranch, [
            'username' => 'inactivebranch',
            'email' => 'inactive.branch@padmamindustries.test',
        ]);

        $this->post(route('login.store'), [
            'login' => 'inactive.branch@padmamindustries.test',
            'password' => 'password123',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $this->loginAsSeededBranchAdmin();

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guest_cannot_access_protected_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
