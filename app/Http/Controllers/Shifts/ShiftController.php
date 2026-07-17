<?php

namespace App\Http\Controllers\Shifts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shifts\ActivateShiftRequest;
use App\Http\Requests\Shifts\CloneShiftRequest;
use App\Http\Requests\Shifts\InactivateShiftRequest;
use App\Http\Requests\Shifts\StoreShiftRequest;
use App\Http\Requests\Shifts\UpdateShiftRequest;
use App\Models\EmployeeType;
use App\Models\Shift;
use App\Services\BranchContext;
use App\Services\Shifts\ShiftCloneService;
use App\Services\Shifts\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function __construct(
        private readonly ShiftService $shiftService,
        private readonly ShiftCloneService $cloneService,
        private readonly BranchContext $branchContext,
    ) {
        $this->authorizeResource(Shift::class, 'shift');
    }

    public function index(Request $request): View
    {
        if (! $this->branchContext->hasActiveBranch()) {
            return view('shifts.index', [
                'shifts' => new LengthAwarePaginator([], 0, 10),
                'filters' => $request->only(['search', 'shift_type', 'employee_type_id', 'overnight', 'effective_status', 'status']),
                'employeeTypes' => collect(),
                'requiresBranchSelection' => true,
            ]);
        }

        $query = Shift::query()->with('employeeTypes');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('shift_code', 'like', "%{$search}%")
                    ->orWhere('shift_name', 'like', "%{$search}%");
            });
        }

        if ($shiftType = $request->string('shift_type')->trim()->value()) {
            $query->where('shift_type', $shiftType);
        }

        if ($employeeTypeId = $request->integer('employee_type_id')) {
            $query->forEmployeeType($employeeTypeId);
        }

        if ($overnight = $request->string('overnight')->trim()->value()) {
            match ($overnight) {
                'overnight' => $query->overnight(),
                'day' => $query->dayShift(),
                default => null,
            };
        }

        if ($effectiveStatus = $request->string('effective_status')->trim()->value()) {
            match ($effectiveStatus) {
                'upcoming' => $query->future(),
                'expired' => $query->expired(),
                'current' => $query->current(),
                default => null,
            };
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $shifts = $query->ordered()->paginate(10)->withQueryString();

        return view('shifts.index', [
            'shifts' => $shifts,
            'filters' => $request->only(['search', 'shift_type', 'employee_type_id', 'overnight', 'effective_status', 'status']),
            'employeeTypes' => EmployeeType::query()->active()->ordered()->get(),
            'requiresBranchSelection' => false,
        ]);
    }

    public function create(): View
    {
        return view('shifts.create', [
            'employeeTypes' => EmployeeType::query()->active()->ordered()->get(),
        ]);
    }

    public function store(StoreShiftRequest $request): RedirectResponse
    {
        $shift = $this->shiftService->create(
            $request->validated(),
            $this->branchContext->currentBranchId(),
            $request->user(),
            $request,
        );

        return redirect()->route('shifts.master.show', $shift)->with('status', 'Shift created successfully.');
    }

    public function show(Shift $shift): View
    {
        $shift->load(['branch', 'employeeTypes', 'createdBy', 'updatedBy']);

        return view('shifts.show', [
            'shift' => $shift,
        ]);
    }

    public function edit(Shift $shift): View
    {
        $shift->load('employeeTypes');

        return view('shifts.edit', [
            'shift' => $shift,
            'employeeTypes' => EmployeeType::query()->active()->ordered()->get(),
        ]);
    }

    public function update(UpdateShiftRequest $request, Shift $shift): RedirectResponse
    {
        $this->shiftService->update($shift, $request->validated(), $request->user(), $request);

        return redirect()->route('shifts.master.show', $shift)->with('status', 'Shift updated successfully.');
    }

    public function activate(ActivateShiftRequest $request, Shift $shift): RedirectResponse
    {
        $this->shiftService->activate($shift, $request->user(), $request);

        return redirect()->route('shifts.master.show', $shift)->with('status', 'Shift activated successfully.');
    }

    public function inactivate(InactivateShiftRequest $request, Shift $shift): RedirectResponse
    {
        $this->shiftService->inactivate($shift, $request->validated('reason'), $request->user(), $request);

        return redirect()->route('shifts.master.show', $shift)->with('status', 'Shift inactivated successfully.');
    }

    public function clone(CloneShiftRequest $request, Shift $shift): RedirectResponse
    {
        $clone = $this->cloneService->clone(
            $shift,
            $request->validated('shift_code'),
            $request->validated('shift_name'),
            $request->user(),
            $request,
        );

        return redirect()->route('shifts.master.edit', $clone)->with('status', 'Shift cloned as a new Draft. Review and activate when ready.');
    }
}
