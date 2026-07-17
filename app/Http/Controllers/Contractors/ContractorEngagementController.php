<?php

namespace App\Http\Controllers\Contractors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contractors\StoreContractorEngagementRequest;
use App\Http\Requests\Contractors\UpdateContractorEngagementRequest;
use App\Models\Contractor;
use App\Models\ContractorBranchEngagement;
use App\Services\BranchContext;
use App\Services\Contractors\ContractorEngagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ContractorEngagementController extends Controller
{
    public function __construct(
        private readonly ContractorEngagementService $engagementService,
        private readonly BranchContext $branchContext,
    ) {
        $this->authorizeResource(ContractorBranchEngagement::class, 'engagement');
    }

    public function index(Request $request): View
    {
        if (! $this->branchContext->hasActiveBranch()) {
            return view('contractors.engagements.index', [
                'engagements' => new LengthAwarePaginator([], 0, 10),
                'filters' => $request->only(['contractor_id', 'agreement_number', 'status']),
                'contractors' => collect(),
                'requiresBranchSelection' => true,
            ]);
        }

        $query = ContractorBranchEngagement::query()->with('contractor');

        if ($contractorId = $request->integer('contractor_id')) {
            $query->where('contractor_id', $contractorId);
        }

        if ($agreementNumber = $request->string('agreement_number')->trim()->value()) {
            $query->where('agreement_number', 'like', "%{$agreementNumber}%");
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $engagements = $query->ordered()->paginate(10)->withQueryString();

        return view('contractors.engagements.index', [
            'engagements' => $engagements,
            'filters' => $request->only(['contractor_id', 'agreement_number', 'status']),
            'contractors' => Contractor::query()->active()->ordered()->get(),
            'requiresBranchSelection' => false,
        ]);
    }

    public function create(): View
    {
        return view('contractors.engagements.create', [
            'contractors' => Contractor::query()->active()->ordered()->get(),
        ]);
    }

    public function store(StoreContractorEngagementRequest $request): RedirectResponse
    {
        $engagement = $this->engagementService->create($request->validated(), $request->user(), $request);

        return redirect()->route('contractors.engagements.show', $engagement)->with('status', 'Branch Engagement created successfully.');
    }

    public function show(ContractorBranchEngagement $engagement): View
    {
        $engagement->load(['contractor', 'branch', 'createdBy', 'updatedBy', 'documents']);

        return view('contractors.engagements.show', [
            'engagement' => $engagement,
            'documentTypes' => config('hrms.contractor_document_types'),
        ]);
    }

    public function edit(ContractorBranchEngagement $engagement): View
    {
        return view('contractors.engagements.edit', [
            'engagement' => $engagement,
        ]);
    }

    public function update(UpdateContractorEngagementRequest $request, ContractorBranchEngagement $engagement): RedirectResponse
    {
        $this->engagementService->update($engagement, $request->validated(), $request->user(), $request);

        return redirect()->route('contractors.engagements.show', $engagement)->with('status', 'Branch Engagement updated successfully.');
    }

    public function activate(Request $request, ContractorBranchEngagement $engagement): RedirectResponse
    {
        $this->authorize('activate', $engagement);

        $this->engagementService->activate($engagement, $request->user(), $request);

        return redirect()->route('contractors.engagements.show', $engagement)->with('status', 'Branch Engagement activated successfully.');
    }

    public function inactivate(Request $request, ContractorBranchEngagement $engagement): RedirectResponse
    {
        $this->authorize('inactivate', $engagement);

        $this->engagementService->inactivate($engagement, $request->user(), $request);

        return redirect()->route('contractors.engagements.show', $engagement)->with('status', 'Branch Engagement inactivated successfully.');
    }
}
