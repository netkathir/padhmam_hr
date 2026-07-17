<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $branchContext = app(BranchContext::class);
        $superAdminRole = Role::query()->where('slug', 'super-administrator')->firstOrFail();
        $branchAdminRole = Role::query()->where('slug', 'branch-administrator')->firstOrFail();
        $headOffice = Branch::query()->where('branch_code', 'HO')->firstOrFail();

        $superAdmin = $branchContext->withoutBranchContext(function () use ($superAdminRole): User {
            $user = User::query()->updateOrCreate(
                ['email' => config('hrms.super_admin.email')],
                [
                    'name' => config('hrms.super_admin.name'),
                    'username' => 'superadmin',
                    'phone' => null,
                    'branch_id' => null,
                    'status' => 'active',
                    'password' => Hash::make(config('hrms.super_admin.password')),
                    'password_changed_at' => now(),
                ]
            );

            $user->roles()->sync([$superAdminRole->id]);

            return $user;
        });

        $branchContext->setBranch($headOffice);

        $branchAdmin = User::query()->updateOrCreate(
            ['email' => config('hrms.branch_admin.email')],
            [
                'name' => config('hrms.branch_admin.name'),
                'username' => 'branchadmin',
                'phone' => null,
                'status' => 'active',
                'password' => Hash::make(config('hrms.branch_admin.password')),
                'password_changed_at' => now(),
                'created_by' => $superAdmin->id,
                'updated_by' => $superAdmin->id,
            ]
        );

        $branchAdmin->roles()->sync([$branchAdminRole->id]);

        $branchContext->clearBranch();
    }
}
