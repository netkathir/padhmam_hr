<?php

namespace App\Http\Controllers\EmployeeShifts;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use Illuminate\View\View;

class EmployeeShiftHistoryController extends Controller
{
    public function index(Employee $employee): View
    {
        $this->authorize('viewHistory', EmployeeShiftAssignment::class);

        $assignments = $employee->shiftAssignments()
            ->with(['shift', 'createdBy'])
            ->ordered()
            ->get();

        return view('employee-shifts.history', [
            'employee' => $employee,
            'assignments' => $assignments,
        ]);
    }
}
