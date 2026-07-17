<?php

namespace App\Http\Controllers\EmployeeShifts;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Services\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * "Shift Assignment Pending" screen (spec section 6): Active Employees who
 * have completed registration but have no Scheduled/Active regular
 * (Fixed/Rotational) Shift assignment yet.
 */
class EmployeeShiftPendingController extends Controller
{
    public function __construct(private readonly BranchContext $branchContext)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeShiftAssignment::class);

        if (! $this->branchContext->hasActiveBranch() && ! $this->branchContext->isAllBranchesSelected()) {
            return view('employee-shifts.pending', [
                'employees' => new LengthAwarePaginator([], 0, 15),
                'filters' => $request->only(['search']),
                'requiresBranchSelection' => true,
            ]);
        }

        $query = Employee::query()
            ->active()
            ->whereNotNull('registration_completed_at')
            ->whereDoesntHave('shiftAssignments', function ($inner): void {
                $inner->whereIn('assignment_type', [EmployeeShiftAssignment::TYPE_FIXED, EmployeeShiftAssignment::TYPE_ROTATIONAL])
                    ->whereIn('status', [EmployeeShiftAssignment::STATUS_SCHEDULED, EmployeeShiftAssignment::STATUS_ACTIVE]);
            })
            ->with(['department', 'designation', 'employeeType']);

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('display_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%");
            });
        }

        $employees = $query->ordered()->paginate(15)->withQueryString();

        return view('employee-shifts.pending', [
            'employees' => $employees,
            'filters' => $request->only(['search']),
            'requiresBranchSelection' => false,
        ]);
    }
}
