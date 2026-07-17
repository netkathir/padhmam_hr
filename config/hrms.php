<?php

return [
    'app_name' => env('HRMS_APP_NAME', 'Padmam Industries HRMS'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),
    'currency' => 'INR',
    'date_format' => 'd-m-Y',
    'branch_all_label' => 'All Branches',
    'branch_context_session_key' => 'hrms.active_branch',
    'branch_context_all_value' => 'all',
    'organization_logo_max_kb' => env('HRMS_ORGANIZATION_LOGO_MAX_KB', 2048),
    'organization' => [
        'code' => env('HRMS_ORGANIZATION_CODE', 'PADMAM'),
        'legal_name' => env('HRMS_ORGANIZATION_LEGAL_NAME', 'Padmam Industries'),
        'display_name' => env('HRMS_ORGANIZATION_DISPLAY_NAME', 'Padmam Industries'),
    ],
    'default_statuses' => [
        'active' => 'active',
        'inactive' => 'inactive',
    ],
    'roles' => [
        ['slug' => 'super-administrator', 'name' => 'Super Administrator'],
        ['slug' => 'hr-administrator', 'name' => 'HR Administrator'],
        ['slug' => 'payroll-administrator', 'name' => 'Payroll Administrator'],
        ['slug' => 'branch-administrator', 'name' => 'Branch Administrator'],
        ['slug' => 'management-user', 'name' => 'Management User'],
        ['slug' => 'employee-user', 'name' => 'Employee User'],
    ],
    'permission_groups' => [
        'Dashboard' => ['dashboard.view'],
        'Organization' => [
            'organization.view',
            'organization.edit',
        ],
        'Branch' => [
            'branch.view',
            'branch.create',
            'branch.edit',
            'branch.activate',
            'branch.inactivate',
            'branch.make-head-office',
        ],
        'User' => [
            'user.view',
            'user.create',
            'user.edit',
            'user.activate',
            'user.inactivate',
        ],
        'Role' => [
            'role.view',
            'role.manage',
        ],
        'Permission' => [
            'permission.view',
            'permission.manage',
        ],
        'Department' => [
            'department.view',
            'department.create',
            'department.edit',
            'department.activate',
            'department.inactivate',
        ],
        'Section' => [
            'section.view',
            'section.create',
            'section.edit',
            'section.activate',
            'section.inactivate',
        ],
        'Designation' => [
            'designation.view',
            'designation.create',
            'designation.edit',
            'designation.activate',
            'designation.inactivate',
        ],
        'Employee Type' => [
            'employee-type.view',
            'employee-type.edit',
            'employee-type.create',
        ],
        'Employee' => [
            'employee.view',
            'employee.create',
            'employee.edit',
            'employee.activate',
            'employee.inactivate',
            'employee.export',
        ],
        'Contractor' => [
            'contractor.view',
            'contractor.create',
            'contractor.edit',
            'contractor.activate',
            'contractor.inactivate',
            'contractor.export',
        ],
        'Attendance' => [
            'attendance.view',
            'attendance.create',
            'attendance.edit',
            'attendance.export',
        ],
        'Leave' => [
            'leave.view',
            'leave.create',
            'leave.edit',
            'leave.approve',
        ],
        'Payroll' => [
            'payroll.view',
            'payroll.create',
            'payroll.edit',
            'payroll.confirm',
            'payroll.close',
            'payroll.reopen',
            'payroll.export',
        ],
        'Report' => [
            'report.view',
            'report.export',
        ],
        'Audit Log' => [
            'audit-log.view',
        ],
        'Application Settings' => [
            'application-settings.view',
            'application-settings.manage',
        ],
    ],
    'super_admin' => [
        'name' => env('HRMS_SUPER_ADMIN_NAME', 'Super Administrator'),
        'email' => env('HRMS_SUPER_ADMIN_EMAIL', 'admin@padmamindustries.test'),
        'password' => env('HRMS_SUPER_ADMIN_PASSWORD', 'ChangeMe123!'),
    ],
    'branch_admin' => [
        'name' => env('HRMS_BRANCH_ADMIN_NAME', 'Branch Administrator'),
        'email' => env('HRMS_BRANCH_ADMIN_EMAIL', 'branch.admin@padmamindustries.test'),
        'password' => env('HRMS_BRANCH_ADMIN_PASSWORD', 'ChangeMe123!'),
        'branch_code' => env('HRMS_BRANCH_ADMIN_BRANCH_CODE', 'HO'),
    ],
    'features' => [
        // Custom Employee Types beyond the three mandatory system
        // classifications (Staff, Company Labour, Contract Labour) are not
        // supported yet. Keep disabled until a future phase implements the
        // dependent Employee Registration numbering and processing rules.
        'custom_employee_types' => env('HRMS_FEATURE_CUSTOM_EMPLOYEE_TYPES', false),
    ],
];
