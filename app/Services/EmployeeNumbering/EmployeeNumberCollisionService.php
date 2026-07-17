<?php

namespace App\Services\EmployeeNumbering;

use App\Models\EmployeeNumberRule;

/**
 * Detects organization-wide format collisions between numbering rules.
 * Employee Numbers are unique across the whole organization (see spec
 * section 25), so two rules that would produce an identical literal string
 * for the same serial value must never both be Active at once.
 */
class EmployeeNumberCollisionService
{
    /**
     * Returns the other Active rule this candidate would collide with, or
     * null when no definite collision is detected. This is a conservative
     * structural check (same resolved skeleton), not a guarantee — the
     * unique database constraint on reservations remains the final guard.
     */
    public function detectDefiniteCollision(EmployeeNumberRule $candidate): ?EmployeeNumberRule
    {
        $skeleton = $this->skeleton($candidate);

        return EmployeeNumberRule::query()
            ->withoutGlobalScopes()
            ->active()
            ->when($candidate->exists, fn ($query) => $query->where('id', '!=', $candidate->id))
            ->with('branch')
            ->get()
            ->first(fn (EmployeeNumberRule $other) => $this->skeleton($other) === $skeleton);
    }

    /**
     * A softer, non-blocking signal: rules that don't include the Branch
     * Code lean entirely on prefix/type/serial to stay unique. Not a
     * definite collision, just an encouragement to strengthen the format.
     */
    public function hasPossibleCollisionRisk(EmployeeNumberRule $rule): bool
    {
        return ! $rule->include_branch_code;
    }

    private function skeleton(EmployeeNumberRule $rule): string
    {
        $branchPart = $rule->include_branch_code ? ($rule->branch?->branch_code ?? '') : '';
        $typePart = $rule->include_employee_type_prefix ? ($rule->employee_type_prefix ?? '') : '';

        return implode('|', [
            $rule->prefix ?? '',
            $branchPart,
            $typePart,
            $rule->separator ?? '',
            $rule->serial_number_length,
        ]);
    }
}
