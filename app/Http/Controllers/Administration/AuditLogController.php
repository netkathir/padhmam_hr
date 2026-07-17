<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\BranchContext;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(private readonly BranchContext $branchContext)
    {
        $this->authorizeResource(AuditLog::class, 'audit_log');
    }

    public function index(): View
    {
        $query = AuditLog::query()->with(['branch', 'user'])->latest('created_at');

        if (! $this->branchContext->isAllBranchesSelected() && $this->branchContext->currentBranchId()) {
            $query->where(function ($builder): void {
                $builder->where('branch_id', $this->branchContext->currentBranchId())
                    ->orWhereNull('branch_id');
            });
        }

        $auditLogs = $query->paginate(20);

        return view('administration.audit-logs.index', compact('auditLogs'));
    }

    public function show(AuditLog $audit_log): View
    {
        return view('administration.audit-logs.show', ['auditLog' => $audit_log]);
    }
}
