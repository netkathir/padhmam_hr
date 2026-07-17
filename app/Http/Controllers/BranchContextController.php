<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchSwitchRequest;
use App\Models\Branch;
use App\Services\AuditService;
use App\Services\BranchContext;
use Illuminate\Http\RedirectResponse;

class BranchContextController extends Controller
{
    public function __construct(
        private readonly BranchContext $branchContext,
        private readonly AuditService $auditService,
    ) {
    }

    public function update(BranchSwitchRequest $request): RedirectResponse
    {
        $previous = $this->branchContext->isAllBranchesSelected()
            ? ['branch_context' => 'all']
            : ['branch_id' => $this->branchContext->currentBranchId()];

        $branchSelection = (string) $request->string('branch_selection');

        if ($branchSelection === 'all') {
            $this->branchContext->setAllBranches();
        } else {
            $branch = Branch::query()
                ->whereKey($branchSelection)
                ->where('status', 'active')
                ->firstOrFail();
            $this->branchContext->setBranch($branch);
        }

        $this->auditService->record('branch_switch', 'branch-context', null, $previous, [
            'branch_context' => $this->branchContext->isAllBranchesSelected() ? 'all' : $this->branchContext->currentBranchId(),
        ], $request);

        return back()->with('status', 'Branch context updated.');
    }
}
