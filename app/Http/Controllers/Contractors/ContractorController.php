<?php

namespace App\Http\Controllers\Contractors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contractors\StoreContractorRequest;
use App\Http\Requests\Contractors\UpdateContractorRequest;
use App\Models\Branch;
use App\Models\Contractor;
use App\Services\BranchContext;
use App\Services\Contractors\ContractorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractorController extends Controller
{
    public function __construct(
        private readonly ContractorService $contractorService,
        private readonly BranchContext $branchContext,
    ) {
        $this->authorizeResource(Contractor::class, 'contractor');
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $isBranchRestricted = ! $user->isSuperAdministrator() && $user->hasRole('branch-administrator');

        $query = Contractor::query()->withCount([
            'branchEngagements as branch_count' => fn ($q) => $q->withoutGlobalScopes(),
            'branchEngagements as active_engagement_count' => fn ($q) => $q->withoutGlobalScopes()->where('status', 'active'),
        ]);

        if ($isBranchRestricted) {
            $query->assignedToBranch($user->branch_id);
        } elseif ($branchId = $request->integer('branch_id')) {
            $query->assignedToBranch($branchId);
        }

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('contractor_code', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('trade_name', 'like', "%{$search}%")
                    ->orWhere('primary_phone', 'like', "%{$search}%");
            });
        }

        if ($type = $request->string('contractor_type')->trim()->value()) {
            $query->where('contractor_type', $type);
        }

        if ($licence = $request->string('licence_validity')->trim()->value()) {
            $warningThreshold = now()->addDays((int) config('hrms.contractor_licence_expiry_warning_days', 30) + 1)->toDateString();

            match ($licence) {
                'expired' => $query->licenceExpired(),
                'expiring_soon' => $query->licenceExpiringSoon(),
                'valid' => $query->where(fn ($inner) => $inner->whereNull('labour_licence_valid_to')->orWhere('labour_licence_valid_to', '>=', $warningThreshold)),
                default => null,
            };
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $contractors = $query->ordered()->paginate(10)->withQueryString();

        return view('contractors.contractors.index', [
            'contractors' => $contractors,
            'filters' => $request->only(['search', 'contractor_type', 'branch_id', 'licence_validity', 'status']),
            'branches' => $isBranchRestricted ? collect() : Branch::query()->active()->ordered()->get(),
            'isBranchRestricted' => $isBranchRestricted,
        ]);
    }

    public function create(): View
    {
        return view('contractors.contractors.create');
    }

    public function store(StoreContractorRequest $request): RedirectResponse
    {
        $contractor = $this->contractorService->create($request->validated(), $request->user(), $request);

        return redirect()->route('contractors.master.show', $contractor)->with('status', 'Contractor created successfully.');
    }

    public function show(Request $request, Contractor $contractor): View
    {
        $user = $request->user();
        $isBranchRestricted = ! $user->isSuperAdministrator() && $user->hasRole('branch-administrator');

        $engagements = $isBranchRestricted
            ? $contractor->branchEngagements()->with('branch')->where('branch_id', $user->branch_id)->get()
            : $contractor->branchEngagements()->withoutGlobalScopes()->with('branch')->ordered()->get();

        $visibleBranchIds = $engagements->pluck('branch_id');

        $documents = $contractor->documents()
            ->with('engagement')
            ->get()
            ->filter(fn ($document) => ! $document->contractor_branch_engagement_id || $visibleBranchIds->contains($document->engagement?->branch_id));

        $contractor->load(['createdBy', 'updatedBy']);

        return view('contractors.contractors.show', [
            'contractor' => $contractor,
            'engagements' => $engagements,
            'documents' => $documents,
            'documentTypes' => config('hrms.contractor_document_types'),
        ]);
    }

    public function edit(Contractor $contractor): View
    {
        return view('contractors.contractors.edit', [
            'contractor' => $contractor,
        ]);
    }

    public function update(UpdateContractorRequest $request, Contractor $contractor): RedirectResponse
    {
        $this->contractorService->update($contractor, $request->validated(), $request->user(), $request);

        return redirect()->route('contractors.master.show', $contractor)->with('status', 'Contractor updated successfully.');
    }

    public function activate(Request $request, Contractor $contractor): RedirectResponse
    {
        $this->authorize('activate', $contractor);

        $this->contractorService->activate($contractor, $request->user(), $request);

        return redirect()->route('contractors.master.show', $contractor)->with('status', 'Contractor activated successfully.');
    }

    public function inactivate(Request $request, Contractor $contractor): RedirectResponse
    {
        $this->authorize('inactivate', $contractor);

        $this->contractorService->inactivate($contractor, $request->user(), $request);

        return redirect()->route('contractors.master.show', $contractor)->with('status', 'Contractor inactivated successfully.');
    }
}
