<?php

namespace App\Services\Masters;

use App\Models\EmployeeType;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeTypeService
{
    /**
     * The only fields a user is ever permitted to change through the
     * Employee Type edit form. Code, is_system, and requires_contractor
     * are intentionally excluded so a manipulated request can never reach
     * the model regardless of what the form request happens to validate.
     */
    private const EDITABLE_FIELDS = [
        'name',
        'description',
        'attendance_applicable',
        'leave_applicable',
        'payroll_applicable',
        'overtime_applicable',
        'default_shift_type',
        'employee_number_prefix',
        'display_order',
        'status',
    ];

    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function update(EmployeeType $employeeType, array $data, User $actor, Request $request): EmployeeType
    {
        $payload = Arr::only($data, self::EDITABLE_FIELDS);

        $isInactivating = ($payload['status'] ?? $employeeType->status) === 'inactive'
            && $employeeType->status === 'active';

        if ($isInactivating && $employeeType->is_system) {
            $this->auditService->record(
                'employee_type_status_change_blocked',
                'employee-type',
                $employeeType,
                ['status' => $employeeType->status],
                ['attempted_status' => 'inactive'],
                $request,
            );

            throw ValidationException::withMessages([
                'status' => 'This is a mandatory system Employee Type and cannot currently be inactivated.',
            ]);
        }

        return DB::transaction(function () use ($employeeType, $payload, $actor, $request): EmployeeType {
            $old = $employeeType->replicate()->toArray();

            $employeeType->fill([
                ...$payload,
                'updated_by' => $actor->id,
            ]);
            $employeeType->save();

            $fresh = $employeeType->fresh();

            $this->auditService->record(
                $this->resolveUpdateEvent($old, $fresh->toArray()),
                'employee-type',
                $employeeType,
                $old,
                $fresh->toArray(),
                $request,
            );

            return $fresh;
        });
    }

    /**
     * Picks the most relevant single audit event name for this update by
     * priority, so the audit trail reads clearly even though every changed
     * field is still recoverable from the full old/new value snapshot.
     */
    private function resolveUpdateEvent(array $old, array $new): string
    {
        $priority = [
            'status' => 'employee_type_status_changed',
            'name' => 'employee_type_name_changed',
            'description' => 'employee_type_description_changed',
            'attendance_applicable' => 'employee_type_attendance_applicable_changed',
            'leave_applicable' => 'employee_type_leave_applicable_changed',
            'payroll_applicable' => 'employee_type_payroll_applicable_changed',
            'overtime_applicable' => 'employee_type_overtime_applicable_changed',
            'default_shift_type' => 'employee_type_default_shift_type_changed',
            'employee_number_prefix' => 'employee_type_employee_number_prefix_changed',
            'display_order' => 'employee_type_display_order_changed',
        ];

        foreach ($priority as $field => $event) {
            if (($old[$field] ?? null) !== ($new[$field] ?? null)) {
                return $event;
            }
        }

        return 'employee_type_updated';
    }
}
