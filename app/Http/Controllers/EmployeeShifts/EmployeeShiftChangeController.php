<?php

namespace App\Http\Controllers\EmployeeShifts;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeShifts\ChangeEmployeeShiftRequest;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use App\Services\EmployeeShifts\EmployeeShiftChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EmployeeShiftChangeController extends Controller
{
    public function __construct(private readonly EmployeeShiftChangeService $changeService)
    {
    }

    public function create(Employee $employee): View
    {
        $this->authorize('change', EmployeeShiftAssignment::class);

        return view('employee-shifts.change', [
            'employee' => $employee->load('currentShiftAssignment.shift'),
            'shifts' => Shift::query()->active()->where('shift_type', Shift::TYPE_FIXED)->ordered()->get(),
        ]);
    }

    public function store(ChangeEmployeeShiftRequest $request, Employee $employee): RedirectResponse
    {
        $assignment = $this->changeService->changeFixedShift(
            $employee,
            [
                'shift_id' => $request->validated('shift_id'),
                'effective_from' => $request->validated('effective_from'),
                'reason' => $request->validated('reason'),
            ],
            $request->user(),
            $request,
        );

        return redirect()->route('employee-shifts.show', $assignment)->with('status', 'Fixed Shift changed successfully.');
    }
}
