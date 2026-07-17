<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\BranchStoreRequest;
use App\Http\Requests\Administration\BranchUpdateRequest;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(private readonly BranchService $branchService)
    {
        $this->authorizeResource(Branch::class, 'branch');
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $query = Branch::query()->with('organization');

        if (! $user->isSuperAdministrator()) {
            $query->where('id', $user->branch_id);
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('branch_code', 'like', "%{$search}%")
                    ->orWhere('branch_name', 'like', "%{$search}%");
            });
        }

        foreach (['branch_type', 'city', 'state', 'status'] as $filter) {
            if ($value = $request->string($filter)->trim()->value()) {
                $query->where($filter, $value);
            }
        }

        if ($request->filled('head_office')) {
            $query->where('is_head_office', $request->boolean('head_office'));
        }

        $branches = $query->ordered()->paginate(10)->withQueryString();

        return view('administration.branches.index', [
            'branches' => $branches,
            'filters' => $request->only(['search', 'branch_type', 'city', 'state', 'status', 'head_office']),
            'branchTypes' => Branch::TYPES,
        ]);
    }

    public function create(): View
    {
        return view('administration.branches.create', [
            'branchTypes' => Branch::TYPES,
        ]);
    }

    public function store(BranchStoreRequest $request): RedirectResponse
    {
        $branch = $this->branchService->create($request->validated(), $request->user(), $request);

        return redirect()->route('branches.show', $branch)->with('status', 'Branch created successfully.');
    }

    public function show(Branch $branch): View
    {
        $branch->load(['organization', 'createdBy', 'updatedBy']);

        return view('administration.branches.show', [
            'branch' => $branch,
            'activeUserCount' => $branch->activeUserCount(),
        ]);
    }

    public function edit(Branch $branch): View
    {
        return view('administration.branches.edit', [
            'branch' => $branch,
            'branchTypes' => Branch::TYPES,
        ]);
    }

    public function update(BranchUpdateRequest $request, Branch $branch): RedirectResponse
    {
        $this->branchService->update($branch, $request->validated(), $request->user(), $request);

        return redirect()->route('branches.show', $branch)->with('status', 'Branch updated successfully.');
    }

    public function activate(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('activate', $branch);

        $this->branchService->activate($branch, $request->user(), $request);

        return redirect()->route('branches.show', $branch)->with('status', 'Branch activated successfully.');
    }

    public function inactivate(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('inactivate', $branch);

        $this->branchService->inactivate($branch, $request->user(), $request);

        return redirect()->route('branches.show', $branch)->with('status', 'Branch inactivated successfully.');
    }

    public function makeHeadOffice(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('makeHeadOffice', $branch);

        $this->branchService->makeHeadOffice($branch, $request->user(), $request);

        return redirect()->route('branches.show', $branch)->with('status', 'Head Office updated successfully.');
    }
}
