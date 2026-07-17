<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeChangeHistory;
use App\Models\User;

/**
 * Supplements the general AuditService with an Employee-focused history
 * trail (spec section 32) for the specific change types called out as
 * significant: Employee Type, Department, Section, Designation, Reporting
 * Manager, Contractor, Shift Type, Fixed Shift, applicability overrides,
 * Status, and Date of Joining correction.
 */
class EmployeeHistoryService
{
    public function record(
        Employee $employee,
        string $changeType,
        array $oldValues,
        array $newValues,
        ?User $actor,
        ?string $reason = null,
        ?string $effectiveDate = null,
    ): EmployeeChangeHistory {
        return EmployeeChangeHistory::create([
            'employee_id' => $employee->id,
            'change_type' => $changeType,
            'effective_date' => $effectiveDate,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'changed_by' => $actor?->id,
        ]);
    }
}
