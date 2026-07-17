<?php

namespace App\Http\Controllers\EmployeeShifts;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeShifts\CancelEmployeeShiftAssignmentRequest;
use App\Http\Requests\EmployeeShifts\StoreEmployeeShiftAssignmentRequest;
use App\Http\Requests\EmployeeShifts\UpdateScheduledShiftAssignmentRequest;
use App\Models\Employee;
use App\Models\EmployeeShiftAssignment;
use App\Models\Shift;
use App\Services\BranchContext;
use App\Services\EmployeeShifts\EmployeeShiftAssignmentService;
use App\Services\EmployeeShifts\EmployeeShiftChangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class EmployeeShiftAssignmentController extends Controller
{
    public function __construct(
        private readonly EmployeeShiftAssignmentService $assignmentService,
        private readonly EmployeeShiftChangeService $changeService,
        private readonly BranchContext $branchContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeShiftAssignment::class);

        if (! $this->branchContext->hasActiveBranch() && ! $this->branchContext->isAllBranchesSelected()) {
            return view('employee-shifts.index', [
                'assignments' => new LengthAwarePaginator([], 0, 10),
                'filters' => $request->only(['search', 'assignment_type', 'status']),
                'requiresBranchSelection' => true,
            ]);
        }

        $query = EmployeeShiftAssignment::query()->with(['employee', 'shift', 'branch']);

        if ($search = $request->string('search')->trim()->value()) {
            $query->whereHas('employee', function ($inner) use ($search): void {
                $inner->where('display_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%");
            });
        }

        if ($type = $request->string('assignment_type')->trim()->value()) {
            $query->where('assignment_type', $type);
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $assignments = $query->ordered()->paginate(15)->withQueryString();

        return view('employee-shifts.index', [
            'assignments' => $assignments,
            'filters' => $request->only(['search', 'assignment_type', 'status']),
            'requiresBranchSelection' => false,
        ]);
    }

    public function show(EmployeeShiftAssignment $assignment): View
    {
        $this->authorize('view', $assignment);

        $assignment->load(['employee', 'shift', 'branch', 'createdBy', 'updatedBy']);

        return view('employee-shifts.show', [
            'assignment' => $assignment,
        ]);
    }

    public function create(Employee $employee): View
    {
        $this->authorize('create', EmployeeShiftAssignment::class);

        return view('employee-shifts.create', [
            'employee' => $employee,
            'shifts' => Shift::query()
                ->active()
                ->where('shift_type', $employee->usesFixedShift() ? Shift::TYPE_FIXED : Shift::TYPE_ROTATIONAL)
                ->ordered()
                ->get(),
        ]);
    }

    public function store(StoreEmployeeShiftAssignmentRequest $request, Employee $employee): RedirectResponse
    {
        $assignment = $this->assignmentService->assign($employee, $request->validated(), $request->user(), $request);

        return redirect()->route('employee-shifts.show', $assignment)->with('status', 'Shift assignment created successfully.');
    }

    public function edit(EmployeeShiftAssignment $assignment): View
    {
        $this->authorize('editScheduled', $assignment);

        $assignment->load('employee');

        return view('employee-shifts.edit', [
            'assignment' => $assignment,
            'shifts' => Shift::query()
                ->active()
                ->where('shift_type', $assignment->assignment_type === EmployeeShiftAssignment::TYPE_ROTATIONAL ? Shift::TYPE_ROTATIONAL : Shift::TYPE_FIXED)
                ->ordered()
                ->get(),
        ]);
    }

    public function update(UpdateScheduledShiftAssignmentRequest $request, EmployeeShiftAssignment $assignment): RedirectResponse
    {
        $this->changeService->updateScheduled($assignment, $request->validated(), $request->user(), $request);

        return redirect()->route('employee-shifts.show', $assignment)->with('status', 'Scheduled Shift assignment updated successfully.');
    }

    public function cancel(CancelEmployeeShiftAssignmentRequest $request, EmployeeShiftAssignment $assignment): RedirectResponse
    {
        $this->changeService->cancel($assignment, $request->validated('cancellation_reason'), $request->user(), $request);

        return redirect()->route('employee-shifts.show', $assignment)->with('status', 'Shift assignment cancelled successfully.');
    }
}
