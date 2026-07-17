<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeAddress;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeContact;
use App\Models\EmployeeEmergencyContact;
use App\Models\EmployeeStatutoryDetail;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeService
{
    /** @var array<string, list<string>> */
    private const CHANGE_EVENTS = [
        'employee_department_changed' => ['department_id'],
        'employee_section_changed' => ['section_id'],
        'employee_designation_changed' => ['designation_id'],
        'employee_reporting_manager_changed' => ['reporting_manager_id'],
        'employee_contractor_changed' => ['contractor_id', 'contractor_branch_engagement_id'],
        'employee_shift_type_changed' => ['shift_type'],
        'employee_fixed_shift_changed' => ['fixed_shift_id'],
        'employee_date_of_joining_changed' => ['date_of_joining'],
        'employee_applicability_overridden' => ['attendance_applicable', 'leave_applicable', 'payroll_applicable', 'overtime_applicable'],
        'employee_personal_details_changed' => ['first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender', 'marital_status', 'blood_group', 'nationality'],
        'employee_photo_changed' => ['photo_path'],
    ];

    /** Statutory keys masked before being written to audit logs (never full values). */
    private const MASKED_STATUTORY_KEYS = ['aadhaar_number', 'pan_number'];

    public function __construct(
        private readonly AuditService $auditService,
        private readonly EmployeeValidationService $validationService,
        private readonly EmployeeShiftValidationService $shiftValidationService,
        private readonly EmployeeContractorValidationService $contractorValidationService,
        private readonly EmployeeHistoryService $historyService,
    ) {
    }

    /**
     * Post-final-registration edit. Branch, Employee Number, and Employee
     * Type are never accepted here (spec sections 33–34). Critical-field
     * changes (Department/Section/Designation/Contractor/Shift/Date of
     * Joining) require the caller to have supplied change_reason — already
     * enforced by UpdateEmployeeRequest — and are recorded individually in
     * Employee history in addition to the general audit log entry.
     */
    public function update(Employee $employee, array $data, User $actor, Request $request): Employee
    {
        $reason = $data['change_reason'] ?? null;
        unset($data['change_reason']);

        $contact = $data['contact'] ?? [];
        $addresses = $data['addresses'] ?? [];
        $statutory = $data['statutory'] ?? [];
        $bank = $data['bank'] ?? [];
        $emergencyContacts = $data['emergency_contacts'] ?? null;
        unset($data['contact'], $data['addresses'], $data['statutory'], $data['bank'], $data['emergency_contacts']);

        $branchId = $employee->branch_id;

        $this->validationService->assertDepartmentSectionDesignation(
            $branchId,
            (int) $data['department_id'],
            $data['section_id'] ?? null,
            (int) $data['designation_id'],
        );

        $this->validationService->assertNoCircularReportingChain($employee->id, $data['reporting_manager_id'] ?? null);

        if ($data['contractor_id'] ?? null) {
            $this->contractorValidationService->assertValid(
                (int) $data['contractor_id'],
                (int) $data['contractor_branch_engagement_id'],
                $branchId,
                \Carbon\Carbon::parse($data['date_of_joining']),
            );
        }

        $this->shiftValidationService->assertValid(
            $data['shift_type'],
            $data['fixed_shift_id'] ?? null,
            $branchId,
            $employee->employeeType,
            \Carbon\Carbon::parse($data['date_of_joining']),
        );

        return DB::transaction(function () use ($employee, $data, $contact, $addresses, $statutory, $bank, $emergencyContacts, $reason, $actor, $request): Employee {
            $old = $employee->replicate()->toArray();
            $events = $this->changedEvents($employee, $data);

            $data['display_name'] = Employee::buildDisplayName(
                $data['first_name'] ?? $employee->first_name,
                $data['middle_name'] ?? $employee->middle_name,
                $data['last_name'] ?? $employee->last_name,
            );

            $employee->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $employee->save();

            $this->syncRelatedRecords($employee, $contact, $addresses, $statutory, $bank, $emergencyContacts, $actor, $request);

            $new = $employee->fresh()->toArray();

            foreach ($events as $event) {
                $this->historyService->record($employee, $event, $old, $new, $actor, $reason);
            }

            $this->auditService->record($events === [] ? 'employee_updated' : $events[0], 'employee', $employee, $old, $new, $request);

            return $employee->fresh();
        });
    }

    /**
     * Employee photos are display avatars (not compliance documents), so
     * they use the `public` disk like the Organization logo — the previous
     * file is only removed after the new one is safely stored.
     */
    public function storePhoto(UploadedFile $file, ?string $previousPath = null): string
    {
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('employee-photos', $filename, 'public');

        if ($previousPath) {
            Storage::disk('public')->delete($previousPath);
        }

        return $path;
    }

    /**
     * Persists the Employee's related single/multi-record tables and
     * audits each one individually when its data actually changed. Shared
     * by both the registration workflow (Draft creation/update, final
     * registration) and post-registration edits, so this logic exists in
     * exactly one place. $actor/$request are optional so this can still be
     * called in contexts where no audit trail is meaningful (none today,
     * but keeps the signature safe for future callers).
     */
    public function syncRelatedRecords(
        Employee $employee,
        array $contact,
        array $addresses,
        array $statutory,
        array $bank,
        ?array $emergencyContacts,
        ?User $actor = null,
        ?Request $request = null,
    ): void {
        if ($this->hasAnyValue($contact, ['personal_mobile', 'alternate_mobile', 'personal_email', 'official_email'])) {
            $before = $employee->contact?->toArray() ?? [];
            $record = EmployeeContact::updateOrCreate(['employee_id' => $employee->id], $contact);
            $this->auditIfChanged('employee_contact_updated', 'employee', $employee, $before, $record->fresh()->toArray(), $request);
        }

        $current = $addresses['current'] ?? [];
        $addressKeys = ['address_line_1', 'address_line_2', 'city', 'district', 'state', 'country', 'postal_code'];

        if ($this->hasAnyValue($current, $addressKeys)) {
            $before = $employee->addresses->firstWhere('address_type', EmployeeAddress::TYPE_CURRENT)?->toArray() ?? [];
            $record = EmployeeAddress::updateOrCreate(
                ['employee_id' => $employee->id, 'address_type' => EmployeeAddress::TYPE_CURRENT],
                [...$current, 'address_type' => EmployeeAddress::TYPE_CURRENT],
            );
            $this->auditIfChanged('employee_address_updated', 'employee', $employee, $before, $record->fresh()->toArray(), $request);
        }

        $permanent = $addresses['permanent'] ?? [];
        $sameAsCurrent = (bool) ($permanent['is_same_as_current'] ?? false);

        if ($sameAsCurrent && $current !== []) {
            $before = $employee->addresses->firstWhere('address_type', EmployeeAddress::TYPE_PERMANENT)?->toArray() ?? [];
            $record = EmployeeAddress::updateOrCreate(
                ['employee_id' => $employee->id, 'address_type' => EmployeeAddress::TYPE_PERMANENT],
                [
                    'address_line_1' => $current['address_line_1'] ?? null,
                    'address_line_2' => $current['address_line_2'] ?? null,
                    'city' => $current['city'] ?? null,
                    'district' => $current['district'] ?? null,
                    'state' => $current['state'] ?? null,
                    'country' => $current['country'] ?? 'India',
                    'postal_code' => $current['postal_code'] ?? null,
                    'is_same_as_current' => true,
                ],
            );
            $this->auditIfChanged('employee_address_updated', 'employee', $employee, $before, $record->fresh()->toArray(), $request);
        } elseif ($this->hasAnyValue($permanent, $addressKeys)) {
            $before = $employee->addresses->firstWhere('address_type', EmployeeAddress::TYPE_PERMANENT)?->toArray() ?? [];
            $record = EmployeeAddress::updateOrCreate(
                ['employee_id' => $employee->id, 'address_type' => EmployeeAddress::TYPE_PERMANENT],
                [...$permanent, 'address_type' => EmployeeAddress::TYPE_PERMANENT, 'is_same_as_current' => false],
            );
            $this->auditIfChanged('employee_address_updated', 'employee', $employee, $before, $record->fresh()->toArray(), $request);
        }

        if ($this->hasAnyValue($statutory, ['aadhaar_number', 'pan_number', 'uan_number', 'pf_number', 'esi_number'])) {
            $before = $this->maskStatutory($employee->statutoryDetail?->toArray() ?? []);
            $record = EmployeeStatutoryDetail::updateOrCreate(['employee_id' => $employee->id], $statutory);
            $this->auditIfChanged('employee_statutory_details_changed', 'employee', $employee, $before, $this->maskStatutory($record->fresh()->toArray()), $request);
        }

        if ($this->hasAnyValue($bank, ['account_holder_name', 'bank_name', 'branch_name', 'account_number', 'account_type', 'ifsc_code'])) {
            $existing = $employee->bankAccounts->firstWhere('is_primary', true);
            $before = $existing ? [...$existing->toArray(), 'account_number' => $existing->maskedAccountNumber()] : [];
            $record = EmployeeBankAccount::updateOrCreate(
                ['employee_id' => $employee->id, 'is_primary' => true],
                [...$bank, 'is_primary' => true, 'status' => 'active'],
            );
            $new = $record->fresh();
            $this->auditIfChanged('employee_bank_details_changed', 'employee', $employee, $before, [...$new->toArray(), 'account_number' => $new->maskedAccountNumber()], $request);
        }

        if ($emergencyContacts !== null) {
            $before = $employee->emergencyContacts->toArray();
            $employee->emergencyContacts()->delete();

            $created = [];

            foreach ($emergencyContacts as $contactData) {
                if (! $this->hasAnyValue($contactData, ['name', 'relationship', 'primary_phone', 'alternate_phone', 'address'])) {
                    continue;
                }

                $created[] = EmployeeEmergencyContact::create([...$contactData, 'employee_id' => $employee->id])->toArray();
            }

            $this->auditIfChanged('employee_emergency_contact_changed', 'employee', $employee, $before, $created, $request);
        }
    }

    private function auditIfChanged(string $event, string $module, Employee $employee, array $old, array $new, ?Request $request): void
    {
        if ($request === null) {
            return;
        }

        $normalize = fn (array $values) => collect($values)->except(['id', 'created_at', 'updated_at'])->toArray();

        if ($normalize($old) === $normalize($new)) {
            return;
        }

        $this->auditService->record($event, $module, $employee, $old, $new, $request);
    }

    private function maskStatutory(array $values): array
    {
        foreach (self::MASKED_STATUTORY_KEYS as $key) {
            if (array_key_exists($key, $values) && $values[$key]) {
                $values[$key] = \App\Models\EmployeeStatutoryDetail::maskLastFour($values[$key]);
            }
        }

        return $values;
    }

    /**
     * Whether any of the given (non-boolean-control) keys carry a
     * non-empty value — used to decide whether a related record is worth
     * persisting at all, since boolean toggles like is_same_as_current or
     * is_primary are always present in the submitted payload and must not
     * by themselves count as "meaningful data".
     */
    private function hasAnyValue(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    /** Date-cast fields need both sides normalized to Y-m-d before comparing — see valueChanged(). */
    private const DATE_FIELDS = ['date_of_joining', 'date_of_birth', 'confirmation_date', 'probation_end_date'];

    private function changedEvents(Employee $employee, array $data): array
    {
        $events = [];

        foreach (self::CHANGE_EVENTS as $event => $fields) {
            foreach ($fields as $field) {
                if (! array_key_exists($field, $data)) {
                    continue;
                }

                if ($this->valueChanged($employee, $field, $data[$field])) {
                    $events[] = $event;

                    break;
                }
            }
        }

        return $events;
    }

    /**
     * getOriginal() on a date-cast attribute returns a Carbon instance whose
     * default __toString() includes a time component, so comparing it
     * against a plain submitted "Y-m-d" string as strings always looks
     * different even when the date is unchanged. Normalize both sides to
     * Y-m-d for date fields; compare everything else as plain strings.
     */
    private function valueChanged(Employee $employee, string $field, mixed $newValue): bool
    {
        $originalValue = $employee->getOriginal($field);

        if (in_array($field, self::DATE_FIELDS, true)) {
            $originalDate = $originalValue ? \Carbon\Carbon::parse($originalValue)->format('Y-m-d') : null;
            $newDate = $newValue ? \Carbon\Carbon::parse($newValue)->format('Y-m-d') : null;

            return $originalDate !== $newDate;
        }

        return (string) $originalValue !== (string) $newValue;
    }
}
