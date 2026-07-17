<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Gate;

class FoundationAuthorizationTest extends FoundationTestCase
{
    public function test_user_without_permission_receives_a_403_response(): void
    {
        $plainUser = $this->createUserInBranch($this->headOffice, [
            'name' => 'Plain User',
            'username' => 'plain.user',
            'email' => 'plain.user@padmamindustries.test',
        ]);

        $this->post(route('login.store'), [
            'login' => $plainUser->email,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_hiding_a_menu_item_is_not_the_only_authorization_control(): void
    {
        $plainUser = $this->createUserInBranch($this->headOffice, [
            'name' => 'Plain User',
            'username' => 'plain.user2',
            'email' => 'plain.user2@padmamindustries.test',
        ]);

        $this->post(route('login.store'), [
            'login' => $plainUser->email,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Users');

        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_super_administrator_has_all_permissions(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('payroll.close'));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('user.edit'));
        $this->assertTrue(Gate::forUser($this->superAdmin)->allows('audit-log.view'));
    }
}
