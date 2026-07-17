<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Models\Contractor;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeType;
use App\Models\Shift;
use App\Services\BranchContext;
use App\Services\Employees\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService,
        private readonly BranchContext $branchContext,
    ) {
        $this->authorizeResource(Employee::class, 'employee');
    }

    public function index(Request $request): View
    {
        if (! $this->branchContext->hasActiveBranch()) {
            return view('employees.index', [
                'employees' => new LengthAwarePaginator([], 0, 15),
                'filters' => $request->only(['search', 'employee_type_id', 'department_id', 'section_id', 'designation_id', 'contractor_id', 'shift_type', 'fixed_shift_id', 'status', 'from', 'to']),
                'employeeTypes' => collect(),
                'departments' => collect(),
                'designations' => collect(),
                'requiresBranchSelection' => true,
            ]);
        }

        $query = Employee::query()->with(['employeeType', 'department', 'section', 'designation', 'contractor', 'fixedShift']);

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

        if ($employeeTypeId = $request->integer('employee_type_id')) {
            $query->where('employee_type_id', $employeeTypeId);
        }

        if ($departmentId = $request->integer('department_id')) {
            $query->forDepartment($departmentId);
        }

        if ($sectionId = $request->integer('section_id')) {
            $query->forSection($sectionId);
        }

        if ($designationId = $request->integer('designation_id')) {
            $query->forDesignation($designationId);
        }

        if ($contractorId = $request->integer('contractor_id')) {
            $query->forContractor($contractorId);
        }

        if ($shiftType = $request->string('shift_type')->trim()->value()) {
            $query->where('shift_type', $shiftType);
        }

        if ($fixedShiftId = $request->integer('fixed_shift_id')) {
            $query->where('fixed_shift_id', $fixedShiftId);
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        if (($from = $request->string('from')->trim()->value()) && ($to = $request->string('to')->trim()->value())) {
            $query->joinedBetween($from, $to);
        }

        $employees = $query->ordered()->paginate(15)->withQueryString();

        return view('employees.index', [
            'employees' => $employees,
            'filters' => $request->only(['search', 'employee_type_id', 'department_id', 'section_id', 'designation_id', 'contractor_id', 'shift_type', 'fixed_shift_id', 'status', 'from', 'to']),
            'employeeTypes' => EmployeeType::query()->active()->ordered()->get(),
            'departments' => Department::query()->active()->ordered()->get(),
            'designations' => Designation::query()->active()->ordered()->get(),
            'requiresBranchSelection' => false,
        ]);
    }

    public function show(Employee $employee): View
    {
        $employee->load([
            'branch', 'employeeType', 'department', 'section', 'designation', 'reportingManager',
            'fixedShift', 'contractor', 'contractorBranchEngagement', 'contact', 'addresses',
            'statutoryDetail', 'bankAccounts', 'emergencyContacts', 'documents', 'separation',
            'createdBy', 'updatedBy',
        ]);

        return view('employees.show', [
            'employee' => $employee,
            'documentTypes' => config('hrms.employee_document_types'),
        ]);
    }

    public function edit(Employee $employee): View
    {
        $employee->load(['contact', 'addresses', 'statutoryDetail', 'bankAccounts', 'emergencyContacts']);

        return view('employees.edit', $this->formData($employee));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->employeeService->storePhoto($request->file('photo'), $employee->photo_path);
        }
        unset($data['photo']);

        $this->employeeService->update($employee, $data, $request->user(), $request);

        return redirect()->route('employees.show', $employee)->with('status', 'Employee updated successfully.');
    }

    /**
     * Shared dropdown/context data for the registration and edit forms.
     */
    public function formData(?Employee $employee = null): array
    {
        $branchId = $this->branchContext->currentBranchId();

        return [
            'employee' => $employee,
            'employeeTypes' => EmployeeType::query()->active()->ordered()->get(),
            'departments' => Department::query()->active()->ordered()->get(),
            'designations' => Designation::query()->active()->ordered()->get(),
            'shifts' => Shift::query()->active()->ordered()->get(),
            'contractors' => Contractor::query()->active()->ordered()->get(),
            'reportingManagerCandidates' => Employee::query()
                ->active()
                ->when($employee, fn ($q) => $q->where('id', '!=', $employee->id))
                ->ordered()
                ->limit(200)
                ->get(['id', 'display_name', 'employee_number']),
        ];
    }
}
