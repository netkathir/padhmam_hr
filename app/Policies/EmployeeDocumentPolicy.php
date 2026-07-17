<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;

class EmployeeDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employee-document.view');
    }

    public function view(User $user, EmployeeDocument $document): bool
    {
        return $user->hasPermissionTo('employee-document.view') && $this->sameBranch($user, $document->employee);
    }

    public function upload(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('employee-document.upload') && $this->sameBranch($user, $employee);
    }

    public function download(User $user, EmployeeDocument $document): bool
    {
        return $user->hasPermissionTo('employee-document.download') && $this->sameBranch($user, $document->employee);
    }

    public function inactivate(User $user, EmployeeDocument $document): bool
    {
        return $user->hasPermissionTo('employee-document.inactivate') && $this->sameBranch($user, $document->employee);
    }

    private function sameBranch(User $user, ?Employee $employee): bool
    {
        return $user->isSuperAdministrator() || $user->branch_id === $employee?->branch_id;
    }
}
