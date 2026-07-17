<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class FoundationTestCase extends TestCase
{
    use RefreshDatabase;

    protected Branch $headOffice;
    protected Branch $factoryUnit;
    protected User $superAdmin;
    protected User $branchAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->headOffice = Branch::query()->where('branch_code', 'HO')->firstOrFail();
        $this->factoryUnit = Branch::query()->where('branch_code', 'FU')->firstOrFail();
        $this->superAdmin = User::query()->where('email', config('hrms.super_admin.email'))->firstOrFail();
        $this->branchAdmin = User::query()->where('email', config('hrms.branch_admin.email'))->firstOrFail();
    }

    protected function loginAsSeededBranchAdmin(): void
    {
        $this->post(route('login.store'), [
            'login' => config('hrms.branch_admin.email'),
            'password' => config('hrms.branch_admin.password'),
        ])->assertRedirect(route('dashboard'));
    }

    protected function loginAsSeededSuperAdmin(): void
    {
        $this->post(route('login.store'), [
            'login' => config('hrms.super_admin.email'),
            'password' => config('hrms.super_admin.password'),
        ])->assertRedirect(route('dashboard'));
    }

    protected function createUserInBranch(Branch $branch, array $attributes = []): User
    {
        $branchContext = app(BranchContext::class);

        return $branchContext->withoutBranchContext(function () use ($branch, $attributes): User {
            return User::query()->create(array_merge([
                'name' => 'Branch User '.uniqid(),
                'username' => 'branchuser'.uniqid(),
                'email' => uniqid('branchuser').'@padmamindustries.test',
                'phone' => null,
                'branch_id' => $branch->id,
                'status' => 'active',
                'password' => 'password123',
            ], $attributes));
        });
    }

    protected function createInactiveUserInBranch(Branch $branch): User
    {
        return $this->createUserInBranch($branch, [
            'status' => 'inactive',
            'username' => 'inactive'.uniqid(),
            'email' => uniqid('inactive').'@padmamindustries.test',
        ]);
    }
}
