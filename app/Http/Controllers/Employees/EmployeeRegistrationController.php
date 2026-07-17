<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\CompleteEmployeeRegistrationRequest;
use App\Http\Requests\Employees\StoreEmployeeDraftRequest;
use App\Models\Contractor;
use App\Models\ContractorBranchEngagement;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeNumberRule;
use App\Models\Section;
use App\Models\Shift;
use App\Services\AuditService;
use App\Services\BranchContext;
use App\Services\EmployeeNumbering\EmployeeNumberPreviewService;
use App\Services\Employees\EmployeeDuplicateDetectionService;
use App\Services\Employees\EmployeeRegistrationService;
use App\Services\Employees\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeRegistrationController extends Controller
{
    public function __construct(
        private readonly EmployeeRegistrationService $registrationService,
        private readonly EmployeeService $employeeService,
        private readonly EmployeeDuplicateDetectionService $duplicateDetectionService,
        private readonly EmployeeNumberPreviewService $numberPreviewService,
        private readonly AuditService $auditService,
        private readonly BranchContext $branchContext,
    ) {
    }

    public function create(EmployeeController $employeeController): View
    {
        $this->authorize('create', Employee::class);

        return view('employees.create', $employeeController->formData());
    }

    public function storeDraft(StoreEmployeeDraftRequest $request, EmployeeController $employeeController): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->employeeService->storePhoto($request->file('photo'));
        }
        unset($data['photo']);

        $employee = $this->registrationService->createDraft($data, $this->branchContext->currentBranchId(), $request->user(), $request);

        return redirect()->route('employees.edit', $employee)->with('status', 'Draft saved. Continue completing the registration when ready.');
    }

    public function updateDraft(StoreEmployeeDraftRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->employeeService->storePhoto($request->file('photo'), $employee->photo_path);
        }
        unset($data['photo']);

        $this->registrationService->updateDraft($employee, $data, $request->user(), $request);

        return redirect()->route('employees.edit', $employee)->with('status', 'Draft saved.');
    }

    public function review(Request $request, Employee $employee): View
    {
        $this->authorize('completeRegistration', $employee);

        $employee->load([
            'employeeType', 'department', 'section', 'designation', 'reportingManager',
            'fixedShift', 'contractor', 'contractorBranchEngagement', 'contact', 'addresses',
            'statutoryDetail', 'bankAccounts', 'emergencyContacts', 'documents',
        ]);

        $preview = null;

        if ($employee->employee_type_id && $employee->date_of_joining) {
            $rule = EmployeeNumberRule::resolveRule($employee->branch_id, $employee->employee_type_id, $employee->date_of_joining);

            if ($rule) {
                $preview = $this->numberPreviewService->previewForRule($rule, $employee->date_of_joining);

                $this->auditService->record(
                    'employee_number_previewed',
                    'employee',
                    $employee,
                    [],
                    ['preview' => $preview['preview'] ?? null],
                    $request,
                );
            }
        }

        $warnings = $this->duplicateDetectionService->warnings([
            'first_name' => $employee->first_name,
            'middle_name' => $employee->middle_name,
            'last_name' => $employee->last_name,
            'date_of_birth' => $employee->date_of_birth,
            'contact' => ['personal_mobile' => $employee->contact?->personal_mobile],
        ], $employee->id);

        return view('employees.review', [
            'employee' => $employee,
            'preview' => $preview,
            'warnings' => $warnings,
            'documentTypes' => config('hrms.employee_document_types'),
        ]);
    }

    public function complete(CompleteEmployeeRegistrationRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->employeeService->storePhoto($request->file('photo'), $employee->photo_path);
        }
        unset($data['photo']);

        $this->registrationService->completeRegistration($employee, $data, $request->user(), $request);

        return redirect()->route('employees.show', $employee)->with('status', 'Employee registration completed successfully.');
    }

    // --- Dynamic dropdown / lookup endpoints (spec section 58) ---

    public function sectionsByDepartment(Request $request, Department $department): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $branchId = $this->branchContext->currentBranchId();

        if (! $branchId || $department->branch_id !== $branchId) {
            abort(404);
        }

        $sections = Section::query()
            ->where('department_id', $department->id)
            ->active()
            ->ordered()
            ->get(['id', 'section_code', 'section_name']);

        return response()->json(['data' => $sections]);
    }

    public function designationsByScope(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $branchId = $this->branchContext->currentBranchId();

        if (! $branchId) {
            abort(404);
        }

        $departmentId = $request->integer('department_id') ?: null;
        $sectionId = $request->integer('section_id') ?: null;

        $designations = Designation::query()
            ->where('branch_id', $branchId)
            ->active()
            ->where(function ($query) use ($departmentId, $sectionId): void {
                $query->whereNull('department_id');

                if ($departmentId) {
                    $query->orWhere(function ($inner) use ($departmentId, $sectionId): void {
                        $inner->where('department_id', $departmentId)->whereNull('section_id');

                        if ($sectionId) {
                            $inner->orWhere(function ($deepest) use ($departmentId, $sectionId): void {
                                $deepest->where('department_id', $departmentId)->where('section_id', $sectionId);
                            });
                        }
                    });
                }
            })
            ->ordered()
            ->get(['id', 'designation_code', 'designation_name', 'department_id', 'section_id']);

        return response()->json(['data' => $designations]);
    }

    public function reportingManagers(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $search = $request->string('search')->trim()->value();
        $excludeId = $request->integer('exclude_id') ?: null;

        $managers = Employee::query()
            ->active()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->when($search, fn ($q) => $q->where(fn ($inner) => $inner->where('display_name', 'like', "%{$search}%")->orWhere('employee_number', 'like', "%{$search}%")))
            ->ordered()
            ->limit(50)
            ->get(['id', 'display_name', 'employee_number']);

        return response()->json(['data' => $managers]);
    }

    public function eligibleContractors(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $branchId = $this->branchContext->currentBranchId();

        if (! $branchId) {
            abort(404);
        }

        $dateOfJoining = $request->filled('date_of_joining') ? Carbon::parse($request->string('date_of_joining')->value()) : now();

        $contractors = Contractor::query()
            ->active()
            ->whereHas('branchEngagements', function ($query) use ($branchId): void {
                $query->withoutGlobalScopes()->where('branch_id', $branchId)->where('status', 'active');
            })
            ->get(['id', 'contractor_code', 'legal_name']);

        $eligible = $contractors->filter(function (Contractor $contractor) use ($branchId, $dateOfJoining) {
            $engagement = ContractorBranchEngagement::query()
                ->withoutGlobalScopes()
                ->where('contractor_id', $contractor->id)
                ->where('branch_id', $branchId)
                ->where('status', 'active')
                ->first();

            return $engagement && $engagement->isValidForEmployeeAssignment($dateOfJoining);
        })->values();

        return response()->json(['data' => $eligible]);
    }

    public function contractorEngagementDetails(Request $request, Contractor $contractor): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $branchId = $this->branchContext->currentBranchId();

        if (! $branchId) {
            abort(404);
        }

        $engagement = ContractorBranchEngagement::query()
            ->withoutGlobalScopes()
            ->where('contractor_id', $contractor->id)
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->first();

        if (! $engagement) {
            return response()->json(['data' => null]);
        }

        return response()->json(['data' => [
            'id' => $engagement->id,
            'agreement_number' => $engagement->agreement_number,
            'contract_start_date' => optional($engagement->contract_start_date)->format('Y-m-d'),
            'contract_end_date' => optional($engagement->contract_end_date)->format('Y-m-d'),
            'labour_licence_number' => Contractor::maskStatutoryNumber($engagement->effectiveLicenceNumber()),
            'licence_valid_to' => optional($engagement->effectiveLicenceValidTo())->format('Y-m-d'),
            'maximum_labour_count' => $engagement->maximum_labour_count,
        ]]);
    }

    public function eligibleFixedShifts(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $branchId = $this->branchContext->currentBranchId();

        if (! $branchId) {
            abort(404);
        }

        $employeeTypeId = $request->integer('employee_type_id') ?: null;
        $dateOfJoining = $request->filled('date_of_joining') ? Carbon::parse($request->string('date_of_joining')->value()) : now();

        $shifts = Shift::query()
            ->where('branch_id', $branchId)
            ->fixed()
            ->active()
            ->when($employeeTypeId, fn ($q) => $q->forEmployeeType($employeeTypeId))
            ->get(['id', 'shift_code', 'shift_name', 'start_time', 'end_time', 'effective_from', 'effective_to'])
            ->filter(fn (Shift $shift) => $shift->isEffectiveOn($dateOfJoining))
            ->values();

        return response()->json(['data' => $shifts->map(fn (Shift $s) => [
            'id' => $s->id,
            'shift_code' => $s->shift_code,
            'shift_name' => $s->shift_name,
            'start_time' => $s->start_time->format('h:i A'),
            'end_time' => $s->end_time->format('h:i A'),
        ])]);
    }

    public function employeeNumberPreview(Request $request): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $branchId = $this->branchContext->currentBranchId();
        $employeeTypeId = $request->integer('employee_type_id');
        $dateOfJoining = $request->filled('date_of_joining') ? Carbon::parse($request->string('date_of_joining')->value()) : now();

        if (! $branchId || ! $employeeTypeId) {
            return response()->json(['data' => null]);
        }

        $rule = EmployeeNumberRule::resolveRule($branchId, $employeeTypeId, $dateOfJoining);

        if (! $rule) {
            return response()->json(['data' => null, 'message' => 'No active Employee Number Rule applies to this Branch, Employee Type, and Date of Joining.']);
        }

        return response()->json(['data' => $this->numberPreviewService->previewForRule($rule, $dateOfJoining)]);
    }
}
