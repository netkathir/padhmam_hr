<?php

namespace App\Http\Controllers\EmployeeShifts;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeShifts\StoreTemporaryShiftAssignmentRequest;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use App\Services\EmployeeShifts\EmployeeShiftChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeTemporaryShiftController extends Controller
{
    public function __construct(private readonly EmployeeShiftChangeService $changeService)
    {
    }

    public function create(Employee $employee): View
    {
        $this->authorize('temporary', EmployeeShiftAssignment::class);

        return view('employee-shifts.temporary', [
            'employee' => $employee,
            'shifts' => Shift::query()->active()->ordered()->get(),
        ]);
    }

    public function store(StoreTemporaryShiftAssignmentRequest $request, Employee $employee): RedirectResponse
    {
        $assignment = $this->changeService->assignTemporary(
            $employee,
            [
                'shift_id' => $request->validated('shift_id'),
                'effective_from' => $request->validated('effective_from'),
                'effective_to' => $request->validated('effective_to'),
                'reason' => $request->validated('reason'),
            ],
            $request->user(),
            $request,
        );

        return redirect()->route('employee-shifts.show', $assignment)->with('status', 'Temporary Shift assignment created successfully.');
    }
}
