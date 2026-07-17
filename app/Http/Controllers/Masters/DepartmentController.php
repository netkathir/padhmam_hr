<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreDepartmentRequest;
use App\Http\Requests\Masters\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\BranchContext;
use App\Services\Masters\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $departmentService,
        private readonly BranchContext $branchContext,
    ) {
        $this->authorizeResource(Department::class, 'department');
    }

    public function index(Request $request): View
    {
        if (! $this->branchContext->hasActiveBranch()) {
            return view('masters.departments.index', [
                'departments' => new LengthAwarePaginator([], 0, 10),
                'filters' => $request->only(['search', 'status']),
                'requiresBranchSelection' => true,
            ]);
        }

        $query = Department::query()->withCount(['sections']);

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('department_code', 'like', "%{$search}%")
                    ->orWhere('department_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $departments = $query->ordered()->paginate(10)->withQueryString();

        return view('masters.departments.index', [
            'departments' => $departments,
            'filters' => $request->only(['search', 'status']),
            'requiresBranchSelection' => false,
        ]);
    }

    public function create(): View
    {
        return view('masters.departments.create');
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = $this->departmentService->create($request->validated(), $request->user(), $request);

        return redirect()->route('masters.departments.show', $department)->with('status', 'Department created successfully.');
    }

    public function show(Department $department): View
    {
        $department->load(['createdBy', 'updatedBy']);
        $sections = $department->sections()->ordered()->get();

        return view('masters.departments.show', [
            'department' => $department,
            'sections' => $sections,
        ]);
    }

    public function edit(Department $department): View
    {
        return view('masters.departments.edit', [
            'department' => $department,
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->departmentService->update($department, $request->validated(), $request->user(), $request);

        return redirect()->route('masters.departments.show', $department)->with('status', 'Department updated successfully.');
    }

    public function activate(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('activate', $department);

        $this->departmentService->activate($department, $request->user(), $request);

        return redirect()->route('masters.departments.show', $department)->with('status', 'Department activated successfully.');
    }

    public function inactivate(Request $request, Department $department): RedirectResponse
    {
        $this->authorize('inactivate', $department);

        $this->departmentService->inactivate($department, $request->user(), $request);

        return redirect()->route('masters.departments.show', $department)->with('status', 'Department inactivated successfully.');
    }
}
