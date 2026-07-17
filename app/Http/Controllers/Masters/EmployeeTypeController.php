<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\UpdateEmployeeTypeRequest;
use App\Models\EmployeeType;
use App\Services\Masters\EmployeeTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeTypeController extends Controller
{
    public function __construct(private readonly EmployeeTypeService $employeeTypeService)
    {
        $this->authorizeResource(EmployeeType::class, 'employeeType');
    }

    public function index(Request $request): View
    {
        $query = EmployeeType::query();

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($shiftType = $request->string('default_shift_type')->trim()->value()) {
            $query->where('default_shift_type', $shiftType);
        }

        if ($request->filled('requires_contractor')) {
            $query->where('requires_contractor', $request->boolean('requires_contractor'));
        }

        if ($request->filled('payroll_applicable')) {
            $query->where('payroll_applicable', $request->boolean('payroll_applicable'));
        }

        if ($status = $request->string('status')->trim()->value()) {
            $query->where('status', $status);
        }

        $employeeTypes = $query->ordered()->paginate(10)->withQueryString();

        return view('masters.employee-types.index', [
            'employeeTypes' => $employeeTypes,
            'filters' => $request->only(['search', 'default_shift_type', 'requires_contractor', 'payroll_applicable', 'status']),
        ]);
    }

    public function show(EmployeeType $employeeType): View
    {
        $employeeType->load(['createdBy', 'updatedBy']);

        return view('masters.employee-types.show', [
            'employeeType' => $employeeType,
        ]);
    }

    public function edit(EmployeeType $employeeType): View
    {
        return view('masters.employee-types.edit', [
            'employeeType' => $employeeType,
        ]);
    }

    public function update(UpdateEmployeeTypeRequest $request, EmployeeType $employeeType): RedirectResponse
    {
        $this->employeeTypeService->update($employeeType, $request->validated(), $request->user(), $request);

        return redirect()->route('masters.employee-types.show', $employeeType)->with('status', 'Employee Type updated successfully.');
    }
}
