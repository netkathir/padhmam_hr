<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreDesignationRequest;
use App\Http\Requests\Masters\UpdateDesignationRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Section;
use App\Services\BranchContext;
use App\Services\Masters\DesignationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DesignationController extends Controller
{
    public function __construct(
        private readonly DesignationService $designationService,
        private readonly BranchContext $branchContext,
    ) {
        $this->authorizeResource(Designation::class, 'designation');
    }

    public function index(Request $request): View
    {
        if (! $this->branchContext->hasActiveBranch()) {
            return view('masters.designations.index', [
                'designations' => new LengthAwarePaginator([], 0, 10),
                'filters' => $request->only(['search', 'scope', 'department_id', 'section_id', 'status']),
                'departments' => collect(),
                'sections' => collect(),
                'requiresBranchSelection' => true,
            ]);
        }

        $departments = Department::query()->ordered()->get();
        $sections = collect();

        $query = Designation::query()->with(['department', 'section']);

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('designation_code', 'like', "%{$search}%")
                    ->orWhere('designation_name', 'like', "%{$search}%");
            });
        }

        if ($scope = $request->string('scope')->trim()->value()) {
            match ($scope) {
                Designation::SCOPE_BRANCH => $query->whereNull('department_id')->whereNull('section_id'),
                Designation::SCOPE_DEPARTMENT => $query->whereNotNull('department_id')->whereNull('section_id'),
                Designation::SCOPE_SECTION => $query->whereNotNull('section_id'),
                default => null,
            };
        }

        if ($departmentId = $request->integer('department_id')) {
            $query->where('department_id', $departmentId);
            $sections = Section::query()->where('department_id', $departmentId)->ordered()->get();
        }

        if ($sectionId = $request->integer('section_id')) {
            $query->where('section_id', $sectionId);
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $designations = $query->ordered()->paginate(10)->withQueryString();

        return view('masters.designations.index', [
            'designations' => $designations,
            'filters' => $request->only(['search', 'scope', 'department_id', 'section_id', 'status']),
            'departments' => $departments,
            'sections' => $sections,
            'requiresBranchSelection' => false,
        ]);
    }

    public function create(): View
    {
        return view('masters.designations.create', [
            'departments' => Department::query()->active()->ordered()->get(),
        ]);
    }

    public function store(StoreDesignationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['scope']);

        $designation = $this->designationService->create($data, $request->user(), $request);

        return redirect()->route('masters.designations.show', $designation)->with('status', 'Designation created successfully.');
    }

    public function show(Designation $designation): View
    {
        $designation->load(['department', 'section', 'createdBy', 'updatedBy']);

        return view('masters.designations.show', [
            'designation' => $designation,
        ]);
    }

    public function edit(Designation $designation): View
    {
        $departments = Department::query()->active()->ordered()->get();

        if ($designation->department && ! $departments->contains('id', $designation->department_id)) {
            $departments->push($designation->department);
        }

        $sections = $designation->department_id
            ? Section::query()->where('department_id', $designation->department_id)->active()->ordered()->get()
            : collect();

        if ($designation->section && ! $sections->contains('id', $designation->section_id)) {
            $sections->push($designation->section);
        }

        return view('masters.designations.edit', [
            'designation' => $designation,
            'departments' => $departments,
            'sections' => $sections,
        ]);
    }

    public function update(UpdateDesignationRequest $request, Designation $designation): RedirectResponse
    {
        $data = $request->validated();
        unset($data['scope']);

        $this->designationService->update($designation, $data, $request->user(), $request);

        return redirect()->route('masters.designations.show', $designation)->with('status', 'Designation updated successfully.');
    }

    public function activate(Request $request, Designation $designation): RedirectResponse
    {
        $this->authorize('activate', $designation);

        $this->designationService->activate($designation, $request->user(), $request);

        return redirect()->route('masters.designations.show', $designation)->with('status', 'Designation activated successfully.');
    }

    public function inactivate(Request $request, Designation $designation): RedirectResponse
    {
        $this->authorize('inactivate', $designation);

        $this->designationService->inactivate($designation, $request->user(), $request);

        return redirect()->route('masters.designations.show', $designation)->with('status', 'Designation inactivated successfully.');
    }
}
