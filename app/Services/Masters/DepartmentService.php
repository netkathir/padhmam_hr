<?php

namespace App\Services\Masters;

use App\Models\Department;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function create(array $data, User $actor, Request $request): Department
    {
        return DB::transaction(function () use ($data, $actor, $request): Department {
            $department = Department::create([
                ...$data,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('department_created', 'department', $department, [], $department->fresh()->toArray(), $request);

            return $department;
        });
    }

    public function update(Department $department, array $data, User $actor, Request $request): Department
    {
        if (($data['status'] ?? $department->status) === 'inactive' && $department->status === 'active') {
            $this->assertCanInactivate($department);
        }

        return DB::transaction(function () use ($department, $data, $actor, $request): Department {
            $old = $department->replicate()->toArray();
            $codeChanged = isset($data['department_code']) && $data['department_code'] !== $department->department_code;

            $department->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $department->save();

            $this->auditService->record(
                $codeChanged ? 'department_code_changed' : 'department_updated',
                'department',
                $department,
                $old,
                $department->fresh()->toArray(),
                $request,
            );

            return $department->fresh();
        });
    }

    public function activate(Department $department, User $actor, Request $request): Department
    {
        return DB::transaction(function () use ($department, $actor, $request): Department {
            $old = $department->replicate()->toArray();

            $department->update([
                'status' => 'active',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('department_activated', 'department', $department, $old, $department->fresh()->toArray(), $request);

            return $department->fresh();
        });
    }

    public function inactivate(Department $department, User $actor, Request $request): Department
    {
        $this->assertCanInactivate($department);

        return DB::transaction(function () use ($department, $actor, $request): Department {
            $old = $department->replicate()->toArray();

            $department->update([
                'status' => 'inactive',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('department_inactivated', 'department', $department, $old, $department->fresh()->toArray(), $request);

            return $department->fresh();
        });
    }

    private function assertCanInactivate(Department $department): void
    {
        if ($department->sections()->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                'department' => 'This Department cannot be inactivated because active Sections are assigned to it. Inactivate the Sections before continuing.',
            ]);
        }
    }
}
