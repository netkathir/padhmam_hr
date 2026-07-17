<?php

namespace App\Services\Masters;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Section;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DesignationService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function create(array $data, User $actor, Request $request): Designation
    {
        return DB::transaction(function () use ($data, $actor, $request): Designation {
            $designation = Designation::create([
                ...$data,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('designation_created', 'designation', $designation, [], $designation->fresh()->toArray(), $request);

            return $designation;
        });
    }

    public function update(Designation $designation, array $data, User $actor, Request $request): Designation
    {
        $newStatus = $data['status'] ?? $designation->status;

        if ($newStatus === 'active') {
            $this->assertParentsActive(
                array_key_exists('department_id', $data) ? $data['department_id'] : $designation->department_id,
                array_key_exists('section_id', $data) ? $data['section_id'] : $designation->section_id,
            );
        }

        return DB::transaction(function () use ($designation, $data, $actor, $request): Designation {
            $old = $designation->replicate()->toArray();
            $oldLevel = $designation->scopeLevel();

            $codeChanged = isset($data['designation_code']) && $data['designation_code'] !== $designation->designation_code;
            $departmentChanged = array_key_exists('department_id', $data) && $data['department_id'] != $designation->department_id;
            $sectionChanged = array_key_exists('section_id', $data) && $data['section_id'] != $designation->section_id;

            $designation->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $designation->save();

            $newLevel = $designation->fresh()->scopeLevel();

            $event = match (true) {
                $oldLevel !== $newLevel => 'designation_scope_changed',
                $departmentChanged => 'designation_department_changed',
                $sectionChanged => 'designation_section_changed',
                $codeChanged => 'designation_code_changed',
                default => 'designation_updated',
            };

            $this->auditService->record($event, 'designation', $designation, $old, $designation->fresh()->toArray(), $request);

            return $designation->fresh();
        });
    }

    public function activate(Designation $designation, User $actor, Request $request): Designation
    {
        $this->assertParentsActive($designation->department_id, $designation->section_id);

        return DB::transaction(function () use ($designation, $actor, $request): Designation {
            $old = $designation->replicate()->toArray();

            $designation->update([
                'status' => 'active',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('designation_activated', 'designation', $designation, $old, $designation->fresh()->toArray(), $request);

            return $designation->fresh();
        });
    }

    public function inactivate(Designation $designation, User $actor, Request $request): Designation
    {
        // Future dependency: block when active Employee assignments reference this Designation
        // once Employee Management is implemented.
        return DB::transaction(function () use ($designation, $actor, $request): Designation {
            $old = $designation->replicate()->toArray();

            $designation->update([
                'status' => 'inactive',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('designation_inactivated', 'designation', $designation, $old, $designation->fresh()->toArray(), $request);

            return $designation->fresh();
        });
    }

    private function assertParentsActive(?int $departmentId, ?int $sectionId): void
    {
        if ($departmentId) {
            $department = Department::withoutGlobalScopes()->find($departmentId);

            if (! $department?->isActive()) {
                throw ValidationException::withMessages([
                    'department_id' => 'This Designation cannot be activated because its Department is inactive.',
                ]);
            }
        }

        if ($sectionId) {
            $section = Section::withoutGlobalScopes()->find($sectionId);

            if (! $section?->isActive()) {
                throw ValidationException::withMessages([
                    'section_id' => 'This Designation cannot be activated because its Section is inactive.',
                ]);
            }
        }
    }
}
