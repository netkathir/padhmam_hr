<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Services\BranchContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    /**
     * Idempotent development seeder: one sample Draft Staff Employee per
     * active Branch, keyed by a stable biometric_identifier so re-running
     * never creates duplicates. Left as Draft deliberately — completing
     * registration requires an Active Employee Number Rule and consumes a
     * real serial number, which this seeder must not do silently.
     */
    public function run(): void
    {
        $branchContext = app(BranchContext::class);
        $staffType = EmployeeType::query()->where('code', EmployeeType::STAFF)->first();

        if (! $staffType) {
            return;
        }

        foreach (Branch::query()->active()->get() as $branch) {
            $branchContext->setBranch($branch);

            $department = Department::query()->where('branch_id', $branch->id)->active()->orderBy('display_order')->first();
            $designation = Designation::query()->where('branch_id', $branch->id)->active()->orderBy('display_order')->first();

            $biometricIdentifier = 'SAMPLE-'.$branch->branch_code.'-STF-01';

            $employee = Employee::query()->firstOrNew(['biometric_identifier' => $biometricIdentifier]);

            $employee->fill([
                'employee_uuid' => $employee->employee_uuid ?? (string) Str::uuid(),
                'employee_type_id' => $staffType->id,
                'first_name' => 'Demo',
                'last_name' => 'Employee',
                'display_name' => Employee::buildDisplayName('Demo', null, 'Employee'),
                'date_of_birth' => now()->subYears(28)->toDateString(),
                'gender' => 'other',
                'nationality' => 'India',
                'date_of_joining' => now()->toDateString(),
                'department_id' => $department?->id,
                'designation_id' => $designation?->id,
                'shift_type' => $staffType->default_shift_type,
                'attendance_applicable' => $staffType->attendance_applicable,
                'leave_applicable' => $staffType->leave_applicable,
                'payroll_applicable' => $staffType->payroll_applicable,
                'overtime_applicable' => $staffType->overtime_applicable,
                'status' => Employee::STATUS_DRAFT,
            ]);

            $employee->save();
        }

        $branchContext->clearBranch();
    }
}
