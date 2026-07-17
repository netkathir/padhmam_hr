<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly BranchContext $branchContext,
    ) {
    }

    public function create(array $data, User $actor, Request $request): Branch
    {
        $organization = Organization::query()->sole();

        return DB::transaction(function () use ($data, $actor, $organization, $request): Branch {
            $branch = Branch::create([
                ...$data,
                'organization_id' => $organization->id,
                'is_head_office' => false,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('branch_create', 'branch', $branch, [], $branch->fresh()->toArray(), $request);

            return $branch;
        });
    }

    public function update(Branch $branch, array $data, User $actor, Request $request): Branch
    {
        return DB::transaction(function () use ($branch, $data, $actor, $request): Branch {
            $old = $branch->replicate()->toArray();
            $codeChanged = isset($data['branch_code']) && $data['branch_code'] !== $branch->branch_code;

            $branch->fill([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $branch->save();

            $this->auditService->record(
                $codeChanged ? 'branch_code_changed' : 'branch_update',
                'branch',
                $branch,
                $old,
                $branch->fresh()->toArray(),
                $request,
            );

            return $branch->fresh();
        });
    }

    public function activate(Branch $branch, User $actor, Request $request): Branch
    {
        return DB::transaction(function () use ($branch, $actor, $request): Branch {
            $old = $branch->replicate()->toArray();

            $branch->update([
                'status' => 'active',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('branch_activated', 'branch', $branch, $old, $branch->fresh()->toArray(), $request);

            return $branch->fresh();
        });
    }

    public function inactivate(Branch $branch, User $actor, Request $request): Branch
    {
        $this->assertCanInactivate($branch);

        return DB::transaction(function () use ($branch, $actor, $request): Branch {
            $old = $branch->replicate()->toArray();

            $branch->update([
                'status' => 'inactive',
                'updated_by' => $actor->id,
            ]);

            $this->auditService->record('branch_inactivated', 'branch', $branch, $old, $branch->fresh()->toArray(), $request);

            return $branch->fresh();
        });
    }

    public function makeHeadOffice(Branch $branch, User $actor, Request $request): Branch
    {
        if (! $branch->isActive()) {
            throw ValidationException::withMessages([
                'branch' => 'Only an active branch can be made the Head Office.',
            ]);
        }

        if ($branch->isHeadOffice()) {
            return $branch;
        }

        return DB::transaction(fn (): Branch => $this->assignHeadOffice($branch, $actor, $request, auditSeparately: true));
    }

    private function assignHeadOffice(Branch $branch, User $actor, Request $request, bool $auditSeparately): Branch
    {
        $previousHeadOffice = Branch::query()
            ->where('organization_id', $branch->organization_id)
            ->where('is_head_office', true)
            ->where('id', '!=', $branch->id)
            ->first();

        if ($previousHeadOffice) {
            $previousOld = $previousHeadOffice->replicate()->toArray();
            $previousHeadOffice->update(['is_head_office' => false, 'updated_by' => $actor->id]);

            if ($auditSeparately) {
                $this->auditService->record(
                    'head_office_changed',
                    'branch',
                    $previousHeadOffice,
                    $previousOld,
                    $previousHeadOffice->fresh()->toArray(),
                    $request,
                );
            }
        }

        $old = $branch->replicate()->toArray();
        $branch->update([
            'is_head_office' => true,
            'branch_type' => Branch::TYPE_HEAD_OFFICE,
            'updated_by' => $actor->id,
        ]);

        if ($auditSeparately) {
            $this->auditService->record('head_office_changed', 'branch', $branch, $old, $branch->fresh()->toArray(), $request);
        }

        return $branch->fresh();
    }

    private function assertCanInactivate(Branch $branch): void
    {
        if ($branch->isHeadOffice()) {
            throw ValidationException::withMessages([
                'branch' => 'This branch cannot be inactivated because it is the current Head Office. Assign another branch as Head Office first.',
            ]);
        }

        if ($branch->users()->where('status', 'active')->exists()) {
            throw ValidationException::withMessages([
                'branch' => 'This branch cannot be inactivated because active users are assigned to it. Reassign or inactivate those users before continuing.',
            ]);
        }

        if ($this->branchContext->currentBranchId() === $branch->id) {
            throw ValidationException::withMessages([
                'branch' => 'This branch cannot be inactivated because it is currently selected as your active branch. Switch to another branch first.',
            ]);
        }

        $remainingActiveBranches = Branch::query()
            ->where('organization_id', $branch->organization_id)
            ->where('status', 'active')
            ->where('id', '!=', $branch->id)
            ->exists();

        if (! $remainingActiveBranches) {
            throw ValidationException::withMessages([
                'branch' => 'This branch cannot be inactivated because it is the only active branch remaining in the organization.',
            ]);
        }
    }
}
