<?php

namespace App\Services\Masters;

use App\Models\Department;
use App\Models\Section;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SectionService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function create(array $data, User $actor, Request $request): Section
    {
        return DB::transaction(function () use ($data, $actor, $request): Section {
            $section = Section::create([
                ...$data,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('section_created', 'section', $section, [], $section->fresh()->toArray(), $request);

            return $section;
        });
    }

    public function update(Section $section, array $data, User $actor, Request $request): Section
    {
        $newStatus = $data['status'] ?? $section->status;
        $newDepartmentId = $data['department_id'] ?? $section->department_id;

        if ($newStatus === 'active') {
            $this->assertDepartmentActive($newDepartmentId);
        }

        if ($newStatus === 'inactive' && $section->status === 'active') {
            $this->assertCanInactivate($section);
        }

        return DB::transaction(function () use ($section, $data, $actor, $request): Section {
            $old = $section->replicate()->toArray();
            $codeChanged = isset($data['section_code']) && $data['section_code'] !== $section->section_code;
            $moved = isset($data['department_id']) && (int) $data['department_id'] !== $section->department_id;

            $section->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $section->save();

            $event = match (true) {
                $moved => 'section_moved',
                $codeChanged => 'section_code_changed',
                default => 'section_updated',
            };

            $this->auditService->record($event, 'section', $section, $old, $section->fresh()->toArray(), $request);

            return $section->fresh();
        });
    }

    public function activate(Section $section, User $actor, Request $request): Section
    {
        $this->assertDepartmentActive($section->department_id);

        return DB::transaction(function () use ($section, $actor, $request): Section {
            $old = $section->replicate()->toArray();

            $section->update([
                'status' => 'active',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('section_activated', 'section', $section, $old, $section->fresh()->toArray(), $request);

            return $section->fresh();
        });
    }

    public function inactivate(Section $section, User $actor, Request $request): Section
    {
        $this->assertCanInactivate($section);

        return DB::transaction(function () use ($section, $actor, $request): Section {
            $old = $section->replicate()->toArray();

            $section->update([
                'status' => 'inactive',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('section_inactivated', 'section', $section, $old, $section->fresh()->toArray(), $request);

            return $section->fresh();
        });
    }

    private function assertDepartmentActive(?int $departmentId): void
    {
        $department = $departmentId ? Department::withoutGlobalScopes()->find($departmentId) : null;

        if (! $department?->isActive()) {
            throw ValidationException::withMessages([
                'department_id' => 'This Section cannot be activated because its Department is inactive.',
            ]);
        }
    }

    private function assertCanInactivate(Section $section): void
    {
        if ($section->designations()->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                'section' => 'This Section cannot be inactivated because active Designations are assigned to it. Inactivate or reassign those Designations before continuing.',
            ]);
        }
    }
}
