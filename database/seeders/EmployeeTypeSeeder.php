<?php

namespace Database\Seeders;

use App\Models\EmployeeType;
use Illuminate\Database\Seeder;

class EmployeeTypeSeeder extends Seeder
{
    /**
     * Idempotent, non-destructive seeding of the mandatory system Employee
     * Types. Uses firstOrCreate keyed by the stable code so that an
     * administrator's edited display name/description/config is never
     * overwritten by re-running this seeder in production. The invariants
     * that must never drift (is_system, and requires_contractor for
     * Contract Labour) are re-asserted on every run as a self-healing
     * safety net, without touching any other configurable field.
     */
    public function run(): void
    {
        $employeeTypes = [
            [
                'code' => EmployeeType::STAFF,
                'name' => 'Staff',
                'description' => 'Permanent or confirmed company staff employees.',
                'requires_contractor' => false,
                'attendance_applicable' => true,
                'leave_applicable' => true,
                'payroll_applicable' => true,
                'overtime_applicable' => false,
                'default_shift_type' => EmployeeType::SHIFT_FIXED,
                'employee_number_prefix' => 'STF',
                'display_order' => 1,
                'status' => 'active',
            ],
            [
                'code' => EmployeeType::COMPANY_LABOUR,
                'name' => 'Company Labour',
                'description' => 'Labour employed directly by the company, not through a contractor.',
                'requires_contractor' => false,
                'attendance_applicable' => true,
                'leave_applicable' => true,
                'payroll_applicable' => true,
                'overtime_applicable' => true,
                'default_shift_type' => EmployeeType::SHIFT_ROTATIONAL,
                'employee_number_prefix' => 'CL',
                'display_order' => 2,
                'status' => 'active',
            ],
            [
                'code' => EmployeeType::CONTRACT_LABOUR,
                'name' => 'Contract Labour',
                'description' => 'Labour engaged through a contractor.',
                'requires_contractor' => true,
                'attendance_applicable' => true,
                'leave_applicable' => false,
                'payroll_applicable' => true,
                'overtime_applicable' => true,
                'default_shift_type' => EmployeeType::SHIFT_ROTATIONAL,
                'employee_number_prefix' => 'CTL',
                'display_order' => 3,
                'status' => 'active',
            ],
        ];

        foreach ($employeeTypes as $definition) {
            $code = $definition['code'];

            $employeeType = EmployeeType::query()->firstOrCreate(
                ['code' => $code],
                [...$definition, 'is_system' => true]
            );

            // Self-healing invariants for mandatory system records, applied
            // even if the row already existed and even if it drifted.
            if (! $employeeType->is_system) {
                $employeeType->is_system = true;
            }

            if ($code === EmployeeType::CONTRACT_LABOUR && ! $employeeType->requires_contractor) {
                $employeeType->requires_contractor = true;
            }

            if ($employeeType->isDirty()) {
                $employeeType->save();
            }
        }
    }
}
