<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionGroups = config('hrms.permission_groups');

        foreach ($permissionGroups as $group => $permissions) {
            foreach ($permissions as $slug) {
                Permission::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'group' => $group,
                        'name' => str($slug)->replace(['.', '-'], ' ')->title()->toString(),
                    ]
                );
            }
        }

        $roles = collect(config('hrms.roles'));

        foreach ($roles as $roleData) {
            Role::query()->updateOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'status' => 'active',
                ]
            );
        }

        $allPermissionIds = Permission::query()->pluck('id')->all();

        $assignments = [
            'super-administrator' => $allPermissionIds,
            'hr-administrator' => array_values(Permission::query()->whereIn('slug', [
                'dashboard.view',
                'organization.view',
                'branch.view',
                'user.view',
                'user.create',
                'user.edit',
                'role.view',
                'audit-log.view',
            ])->pluck('id')->all()),
            'payroll-administrator' => array_values(Permission::query()->whereIn('slug', [
                'dashboard.view',
                'organization.view',
                'branch.view',
                'audit-log.view',
            ])->pluck('id')->all()),
            'branch-administrator' => array_values(Permission::query()->whereIn('slug', [
                'dashboard.view',
                'branch.view',
                'user.view',
                'user.create',
                'user.edit',
                'audit-log.view',
            ])->pluck('id')->all()),
            'management-user' => array_values(Permission::query()->whereIn('slug', [
                'dashboard.view',
                'organization.view',
                'branch.view',
            ])->pluck('id')->all()),
            'employee-user' => array_values(Permission::query()->whereIn('slug', [
                'dashboard.view',
            ])->pluck('id')->all()),
        ];

        foreach ($assignments as $roleSlug => $permissionIds) {
            $role = Role::query()->where('slug', $roleSlug)->first();

            if ($role) {
                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
