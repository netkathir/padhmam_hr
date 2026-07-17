<?php

namespace App\Http\Controllers;

use App\Services\BranchContext;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly BranchContext $branchContext)
    {
    }

    public function __invoke(): View
    {
        $user = auth()->user();

        return view('dashboard', [
            'currentDate' => Carbon::now()->format(config('hrms.date_format')),
            'activeBranch' => $this->branchContext->isAllBranchesSelected()
                ? config('hrms.branch_all_label')
                : $this->branchContext->currentBranch()?->name,
            'user' => $user,
        ]);
    }
}
