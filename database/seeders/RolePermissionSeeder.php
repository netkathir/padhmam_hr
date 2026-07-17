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
                'department.view',
                'department.create',
                'department.edit',
                'department.activate',
                'department.inactivate',
                'section.view',
                'section.create',
                'section.edit',
                'section.activate',
                'section.inactivate',
                'designation.view',
                'designation.create',
                'designation.edit',
                'designation.activate',
                'designation.inactivate',
                'employee-type.view',
                'employee-type.edit',
                'contractor.view',
                'contractor.create',
                'contractor.edit',
                'contractor.activate',
                'contractor.inactivate',
                'contractor-engagement.view',
                'contractor-engagement.create',
                'contractor-engagement.edit',
                'contractor-engagement.activate',
                'contractor-engagement.inactivate',
                'contractor-document.view',
                'contractor-document.upload',
                'contractor-document.inactivate',
            ])->pluck('id')->all()),
            'payroll-administrator' => array_values(Permission::query()->whereIn('slug', [
                'dashboard.view',
                'organization.view',
                'branch.view',
                'audit-log.view',
                'department.view',
                'section.view',
                'designation.view',
                'employee-type.view',
                'contractor.view',
                'contractor-engagement.view',
                'contractor-document.view',
            ])->pluck('id')->all()),
            'branch-administrator' => array_values(Permission::query()->whereIn('slug', [
                'dashboard.view',
                'branch.view',
                'user.view',
                'user.create',
                'user.edit',
                'audit-log.view',
                'department.view',
                'department.create',
                'department.edit',
                'section.view',
                'section.create',
                'section.edit',
                'designation.view',
                'designation.create',
                'designation.edit',
                'employee-type.view',
                'contractor.view',
                'contractor-engagement.view',
                'contractor-engagement.create',
                'contractor-engagement.edit',
                'contractor-engagement.activate',
                'contractor-engagement.inactivate',
                'contractor-document.view',
                'contractor-document.upload',
            ])->pluck('id')->all()),
            'management-user' => array_values(Permission::query()->whereIn('slug', [
                'dashboard.view',
                'organization.view',
                'branch.view',
                'department.view',
                'section.view',
                'designation.view',
                'employee-type.view',
                'contractor.view',
                'contractor-engagement.view',
                'contractor-document.view',
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
