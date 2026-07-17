<?php

namespace App\Http\Controllers\EmployeeNumbering;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeNumbering\AdjustEmployeeNumberSequenceRequest;
use App\Models\EmployeeNumberRule;
use App\Models\EmployeeNumberSequence;
use App\Services\BranchContext;
use App\Services\EmployeeNumbering\EmployeeNumberSequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class EmployeeNumberSequenceController extends Controller
{
    public function __construct(
        private readonly EmployeeNumberSequenceService $sequenceService,
        private readonly BranchContext $branchContext,
    ) {
        $this->authorizeResource(EmployeeNumberSequence::class, 'sequence');
    }

    public function index(Request $request): View
    {
        if (! $this->branchContext->hasActiveBranch()) {
            return view('employee-numbering.sequences.index', [
                'sequences' => new LengthAwarePaginator([], 0, 10),
                'filters' => $request->only(['rule_id', 'status']),
                'rules' => collect(),
                'requiresBranchSelection' => true,
            ]);
        }

        $ruleIds = EmployeeNumberRule::query()->pluck('id');

        $query = EmployeeNumberSequence::query()
            ->whereIn('employee_number_rule_id', $ruleIds)
            ->with('rule.branch', 'rule.employeeType');

        if ($ruleId = $request->integer('rule_id')) {
            $query->where('employee_number_rule_id', $ruleId);
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->whereHas('rule', fn ($inner) => $inner->where('status', $status));
        }

        $sequences = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('employee-numbering.sequences.index', [
            'sequences' => $sequences,
            'filters' => $request->only(['rule_id', 'status']),
            'rules' => EmployeeNumberRule::query()->ordered()->get(),
            'requiresBranchSelection' => false,
        ]);
    }

    public function show(EmployeeNumberSequence $sequence): View
    {
        $sequence->load(['rule.branch', 'rule.employeeType']);

        return view('employee-numbering.sequences.show', [
            'sequence' => $sequence,
        ]);
    }

    public function adjust(AdjustEmployeeNumberSequenceRequest $request, EmployeeNumberSequence $sequence): RedirectResponse
    {
        $this->sequenceService->adjustNextNumber(
            $sequence,
            (int) $request->validated('next_number'),
            $request->validated('reason'),
            $request->user(),
            $request,
        );

        return redirect()->route('employee-numbering.sequences.show', $sequence)->with('status', 'Sequence next number adjusted successfully.');
    }
}
