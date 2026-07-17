<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeContact;
use App\Models\EmployeeStatutoryDetail;

/**
 * Pre-registration duplicate detection (spec section 45). Definite unique
 * identifier duplicates (PAN, Aadhaar, bank account number) are blocking;
 * name+DOB and mobile-number matches are warnings the user may proceed past
 * with acknowledgement. Biometric identifier duplicates are already
 * prevented by a database unique constraint, so they are not re-checked
 * here.
 *
 * Aadhaar and bank account numbers are application-encrypted with a random
 * IV per value, so they cannot be matched with a SQL WHERE clause — this
 * performs a decrypt-and-compare scan instead. Acceptable for a single
 * organization's employee directory; a deterministic lookup hash would be
 * needed if this ever had to scale to a very large multi-tenant dataset.
 */
class EmployeeDuplicateDetectionService
{
    public function definiteDuplicateErrors(array $data, ?int $excludeEmployeeId = null): array
    {
        $errors = [];

        if ($pan = $data['statutory']['pan_number'] ?? null) {
            $pan = strtoupper(trim($pan));

            $exists = EmployeeStatutoryDetail::query()
                ->where('pan_number', $pan)
                ->whereHas('employee', fn ($q) => $excludeEmployeeId ? $q->where('id', '!=', $excludeEmployeeId) : $q)
                ->exists();

            if ($exists) {
                $errors['statutory.pan_number'] = 'This PAN is already registered to another Employee.';
            }
        }

        if ($aadhaar = $data['statutory']['aadhaar_number'] ?? null) {
            if ($this->encryptedValueExistsForAnotherEmployee(EmployeeStatutoryDetail::class, 'aadhaar_number', preg_replace('/\s+/', '', $aadhaar), $excludeEmployeeId)) {
                $errors['statutory.aadhaar_number'] = 'This Aadhaar number is already registered to another Employee.';
            }
        }

        if ($accountNumber = $data['bank']['account_number'] ?? null) {
            if ($this->encryptedValueExistsForAnotherEmployee(EmployeeBankAccount::class, 'account_number', $accountNumber, $excludeEmployeeId)) {
                $errors['bank.account_number'] = 'This bank account number is already registered to another Employee.';
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public function warnings(array $data, ?int $excludeEmployeeId = null): array
    {
        $warnings = [];

        if (! empty($data['first_name']) && ! empty($data['date_of_birth'])) {
            $displayName = Employee::buildDisplayName(
                $data['first_name'] ?? null,
                $data['middle_name'] ?? null,
                $data['last_name'] ?? null,
            );

            $matches = Employee::query()
                ->withoutGlobalScopes()
                ->whereDate('date_of_birth', $data['date_of_birth'])
                ->whereRaw('LOWER(display_name) = ?', [strtolower($displayName)])
                ->when($excludeEmployeeId, fn ($q) => $q->where('id', '!=', $excludeEmployeeId))
                ->exists();

            if ($matches) {
                $warnings[] = 'Another Employee with the same name and date of birth already exists.';
            }
        }

        if ($mobile = $data['contact']['personal_mobile'] ?? null) {
            $matches = EmployeeContact::query()
                ->where('personal_mobile', trim($mobile))
                ->whereHas('employee', fn ($q) => $excludeEmployeeId ? $q->where('id', '!=', $excludeEmployeeId) : $q)
                ->exists();

            if ($matches) {
                $warnings[] = 'Another Employee is already registered with the same personal mobile number.';
            }
        }

        return $warnings;
    }

    private function encryptedValueExistsForAnotherEmployee(string $modelClass, string $column, string $plainValue, ?int $excludeEmployeeId): bool
    {
        $query = $modelClass::query()->whereNotNull($column);

        if ($excludeEmployeeId) {
            $query->where('employee_id', '!=', $excludeEmployeeId);
        }

        foreach ($query->get([$column, 'employee_id']) as $row) {
            if ($row->{$column} === $plainValue) {
                return true;
            }
        }

        return false;
    }
}
