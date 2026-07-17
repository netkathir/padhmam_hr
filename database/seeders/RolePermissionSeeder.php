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
                'employee-number-rule.view',
                'employee-number-rule.create',
                'employee-number-rule.edit',
                'employee-number-rule.activate',
                'employee-number-rule.inactivate',
                'employee-number-rule.preview',
                'employee-number-sequence.view',
                'shift.view',
                'shift.create',
                'shift.edit',
                'shift.activate',
                'shift.inactivate',
                'shift.clone',
                'employee.view',
                'employee.create',
                'employee.edit',
                'employee.complete-registration',
                'employee.activate',
                'employee.inactivate',
                'employee.reactivate',
                'employee.separate',
                'employee.view-sensitive',
                'employee.edit-sensitive',
                'employee.view-history',
                'employee-document.view',
                'employee-document.upload',
                'employee-document.download',
                'employee-document.inactivate',
                'employee-shift-assignment.view',
                'employee-shift-assignment.create',
                'employee-shift-assignment.edit-scheduled',
                'employee-shift-assignment.change',
                'employee-shift-assignment.temporary',
                'employee-shift-assignment.cancel',
                'employee-shift-assignment.view-history',
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
                'employee-number-rule.view',
                'employee-number-sequence.view',
                'shift.view',
                'employee.view',
                'employee.view-sensitive',
                'employee-document.view',
                'employee-shift-assignment.view',
                'employee-shift-assignment.view-history',
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
                'employee-number-rule.view',
                'employee-number-rule.create',
                'employee-number-rule.edit',
                'employee-number-rule.preview',
                'shift.view',
                'shift.create',
                'shift.edit',
                'shift.clone',
                'employee.view',
                'employee.create',
                'employee.edit',
                'employee.view-history',
                'employee-document.view',
                'employee-document.upload',
                'employee-document.download',
                'employee-shift-assignment.view',
                'employee-shift-assignment.view-history',
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
                'employee-number-rule.view',
                'employee-number-sequence.view',
                'shift.view',
                'employee.view',
                'employee-document.view',
                'employee-shift-assignment.view',
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
