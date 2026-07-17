<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreSectionRequest;
use App\Http\Requests\Masters\UpdateSectionRequest;
use App\Models\Department;
use App\Models\Section;
use App\Services\BranchContext;
use App\Services\Masters\SectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function __construct(
        private readonly SectionService $sectionService,
        private readonly BranchContext $branchContext,
    ) {
        $this->authorizeResource(Section::class, 'section');
    }

    public function index(Request $request): View
    {
        if (! $this->branchContext->hasActiveBranch()) {
            return view('masters.sections.index', [
                'sections' => new LengthAwarePaginator([], 0, 10),
                'filters' => $request->only(['search', 'department_id', 'status']),
                'departments' => collect(),
                'requiresBranchSelection' => true,
            ]);
        }

        $departments = Department::query()->ordered()->get();

        $query = Section::query()->with('department');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('section_code', 'like', "%{$search}%")
                    ->orWhere('section_name', 'like', "%{$search}%");
            });
        }

        if ($departmentId = $request->integer('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $sections = $query->ordered()->paginate(10)->withQueryString();

        return view('masters.sections.index', [
            'sections' => $sections,
            'filters' => $request->only(['search', 'department_id', 'status']),
            'departments' => $departments,
            'requiresBranchSelection' => false,
        ]);
    }

    public function create(): View
    {
        return view('masters.sections.create', [
            'departments' => Department::query()->active()->ordered()->get(),
        ]);
    }

    public function store(StoreSectionRequest $request): RedirectResponse
    {
        $section = $this->sectionService->create($request->validated(), $request->user(), $request);

        return redirect()->route('masters.sections.show', $section)->with('status', 'Section created successfully.');
    }

    public function show(Section $section): View
    {
        $section->load(['department', 'createdBy', 'updatedBy']);

        return view('masters.sections.show', [
            'section' => $section,
        ]);
    }

    public function edit(Section $section): View
    {
        $departments = Department::query()->active()->ordered()->get();

        if ($section->department && ! $departments->contains('id', $section->department_id)) {
            $departments->push($section->department);
        }

        return view('masters.sections.edit', [
            'section' => $section,
            'departments' => $departments,
        ]);
    }

    public function update(UpdateSectionRequest $request, Section $section): RedirectResponse
    {
        $this->sectionService->update($section, $request->validated(), $request->user(), $request);

        return redirect()->route('masters.sections.show', $section)->with('status', 'Section updated successfully.');
    }

    public function activate(Request $request, Section $section): RedirectResponse
    {
        $this->authorize('activate', $section);

        $this->sectionService->activate($section, $request->user(), $request);

        return redirect()->route('masters.sections.show', $section)->with('status', 'Section activated successfully.');
    }

    public function inactivate(Request $request, Section $section): RedirectResponse
    {
        $this->authorize('inactivate', $section);

        $this->sectionService->inactivate($section, $request->user(), $request);

        return redirect()->route('masters.sections.show', $section)->with('status', 'Section inactivated successfully.');
    }

    public function byDepartment(Request $request, Department $department): JsonResponse
    {
        $this->authorize('viewAny', Section::class);

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
}
