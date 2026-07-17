<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\ActivateEmployeeRequest;
use App\Http\Requests\Employees\InactivateEmployeeRequest;
use App\Http\Requests\Employees\ReactivateEmployeeRequest;
use App\Models\Employee;
use App\Services\Employees\EmployeeStatusService;
use Illuminate\Http\RedirectResponse;

class EmployeeStatusController extends Controller
{
    public function __construct(private readonly EmployeeStatusService $statusService)
    {
    }

    public function activate(ActivateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->statusService->activate($employee, $request->user(), $request);

        return redirect()->route('employees.show', $employee)->with('status', 'Employee activated successfully.');
    }

    public function inactivate(InactivateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->statusService->inactivate(
            $employee,
            $request->validated('effective_date'),
            $request->validated('reason'),
            $request->user(),
            $request,
        );

        return redirect()->route('employees.show', $employee)->with('status', 'Employee inactivated successfully.');
    }

    public function reactivate(ReactivateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->statusService->reactivate($employee, $request->validated('reason'), $request->user(), $request);

        return redirect()->route('employees.show', $employee)->with('status', 'Employee reactivated successfully.');
    }
}
