<?php

namespace App\Http\Controllers\Employees;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\SeparateEmployeeRequest;
use App\Models\Employee;
use App\Services\Employees\EmployeeStatusService;
use Illuminate\Http\RedirectResponse;

class EmployeeSeparationController extends Controller
{
    public function __construct(private readonly EmployeeStatusService $statusService)
    {
    }

    public function store(SeparateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->statusService->separate($employee, $request->validated(), $request->user(), $request);

        return redirect()->route('employees.show', $employee)->with('status', 'Employee separation recorded successfully.');
    }
}
