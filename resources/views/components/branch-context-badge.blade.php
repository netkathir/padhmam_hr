@php
    $branchContext = app(\App\Services\BranchContext::class);
    $isAllBranches = $branchContext->isAllBranchesSelected();
    $activeBranch = $branchContext->currentBranch();
@endphp

<div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light mb-3">
    <i class="bi bi-geo-alt text-primary"></i>
    <span class="text-muted small">Active Branch:</span>
    <span class="fw-semibold">
        {{ $isAllBranches ? config('hrms.branch_all_label') : ($activeBranch?->branch_name ?? 'Not selected') }}
    </span>
</div>
