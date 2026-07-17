<?php

namespace App\Http\Controllers\EmployeeNumbering;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeNumbering\ActivateEmployeeNumberRuleRequest;
use App\Http\Requests\EmployeeNumbering\PreviewEmployeeNumberRuleRequest;
use App\Http\Requests\EmployeeNumbering\StoreEmployeeNumberRuleRequest;
use App\Http\Requests\EmployeeNumbering\UpdateEmployeeNumberRuleRequest;
use App\Models\EmployeeNumberRule;
use App\Models\EmployeeType;
use App\Services\BranchContext;
use App\Services\EmployeeNumbering\EmployeeNumberCollisionService;
use App\Services\EmployeeNumbering\EmployeeNumberPreviewService;
use App\Services\EmployeeNumbering\EmployeeNumberRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class EmployeeNumberRuleController extends Controller
{
    public function __construct(
        private readonly EmployeeNumberRuleService $ruleService,
        private readonly EmployeeNumberPreviewService $previewService,
        private readonly EmployeeNumberCollisionService $collisionService,
        private readonly BranchContext $branchContext,
    ) {
        $this->authorizeResource(EmployeeNumberRule::class, 'rule');
    }

    public function index(Request $request): View
    {
        if (! $this->branchContext->hasActiveBranch()) {
            return view('employee-numbering.rules.index', [
                'rules' => new LengthAwarePaginator([], 0, 10),
                'filters' => $request->only(['employee_type_id', 'status', 'reset_frequency', 'effective_status', 'search']),
                'employeeTypes' => collect(),
                'requiresBranchSelection' => true,
            ]);
        }

        $query = EmployeeNumberRule::query()->with(['employeeType', 'branch']);

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('rule_name', 'like', "%{$search}%")
                    ->orWhere('prefix', 'like', "%{$search}%");
            });
        }

        if ($employeeTypeId = $request->integer('employee_type_id')) {
            $query->where('employee_type_id', $employeeTypeId);
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        if ($resetFrequency = $request->string('reset_frequency')->trim()->value()) {
            $query->where('reset_frequency', $resetFrequency);
        }

        if ($effectiveStatus = $request->string('effective_status')->trim()->value()) {
            match ($effectiveStatus) {
                'upcoming' => $query->future(),
                'expired' => $query->expired(),
                'current' => $query->current(),
                default => null,
            };
        }

        $rules = $query->ordered()->paginate(10)->withQueryString();

        $previews = [];

        foreach ($rules as $rule) {
            $previews[$rule->id] = $this->previewService->previewForRule($rule);
        }

        return view('employee-numbering.rules.index', [
            'rules' => $rules,
            'previews' => $previews,
            'filters' => $request->only(['employee_type_id', 'status', 'reset_frequency', 'effective_status', 'search']),
            'employeeTypes' => EmployeeType::query()->active()->ordered()->get(),
            'requiresBranchSelection' => false,
        ]);
    }

    public function create(): View
    {
        return view('employee-numbering.rules.create', [
            'employeeTypes' => EmployeeType::query()->active()->ordered()->get(),
        ]);
    }

    public function store(StoreEmployeeNumberRuleRequest $request): RedirectResponse
    {
        $rule = $this->ruleService->create(
            $request->validated(),
            $this->branchContext->currentBranchId(),
            $request->user(),
            $request,
        );

        return redirect()->route('employee-numbering.rules.show', $rule)->with('status', 'Employee Number Rule created successfully.');
    }

    public function show(EmployeeNumberRule $rule): View
    {
        $rule->load(['branch', 'employeeType', 'createdBy', 'updatedBy']);
        $sequences = $rule->sequences()->orderByDesc('id')->get();

        $isOnlyApplicableRule = $rule->isActive() && ! EmployeeNumberRule::query()
            ->withoutGlobalScopes()
            ->where('id', '!=', $rule->id)
            ->where('branch_id', $rule->branch_id)
            ->where('employee_type_id', $rule->employee_type_id)
            ->current()
            ->exists();

        return view('employee-numbering.rules.show', [
            'rule' => $rule,
            'sequences' => $sequences,
            'preview' => $this->previewService->previewForRule($rule),
            'collisionRisk' => $this->collisionService->hasPossibleCollisionRisk($rule),
            'isOnlyApplicableRule' => $isOnlyApplicableRule,
        ]);
    }

    public function edit(EmployeeNumberRule $rule): View
    {
        return view('employee-numbering.rules.edit', [
            'rule' => $rule,
            'employeeTypes' => EmployeeType::query()->active()->ordered()->get(),
        ]);
    }

    public function update(UpdateEmployeeNumberRuleRequest $request, EmployeeNumberRule $rule): RedirectResponse
    {
        $this->ruleService->update($rule, $request->validated(), $request->user(), $request);

        return redirect()->route('employee-numbering.rules.show', $rule)->with('status', 'Employee Number Rule updated successfully.');
    }

    public function activate(ActivateEmployeeNumberRuleRequest $request, EmployeeNumberRule $rule): RedirectResponse
    {
        $this->ruleService->activate($rule, $request->user(), $request);

        return redirect()->route('employee-numbering.rules.show', $rule)->with('status', 'Employee Number Rule activated successfully.');
    }

    public function inactivate(Request $request, EmployeeNumberRule $rule): RedirectResponse
    {
        $this->authorize('inactivate', $rule);

        $this->ruleService->inactivate($rule, $request->string('reason')->trim()->value() ?: null, $request->user(), $request);

        return redirect()->route('employee-numbering.rules.show', $rule)->with('status', 'Employee Number Rule inactivated successfully.');
    }

    public function createVersion(Request $request, EmployeeNumberRule $rule): RedirectResponse
    {
        $this->authorize('createVersion', $rule);

        $newVersion = $this->ruleService->createNewVersion($rule, $request->user(), $request);

        return redirect()->route('employee-numbering.rules.edit', $newVersion)->with('status', 'A new Draft version has been created. Configure and activate it when ready.');
    }

    public function preview(PreviewEmployeeNumberRuleRequest $request): JsonResponse
    {
        if ($request->filled('rule_id')) {
            $rule = EmployeeNumberRule::query()->findOrFail($request->input('rule_id'));

            return response()->json($this->previewService->previewForRule($rule));
        }

        $employeeType = EmployeeType::query()->findOrFail($request->input('employee_type_id'));
        $branch = $this->branchContext->currentBranch();

        return response()->json($this->previewService->previewFromAttributes($request->validated(), $branch, $employeeType));
    }
}
