<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BranchContext
{
    public function __construct()
    {
    }

    public function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    public function currentBranchId(): ?int
    {
        if ($this->isAllBranchesSelected()) {
            return null;
        }

        $branchId = Session::get($this->sessionKey().'.id');

        return $branchId ? (int) $branchId : null;
    }

    public function currentBranch(): ?Branch
    {
        $branchId = $this->currentBranchId();

        if (! $branchId) {
            return null;
        }

        return Branch::query()->find($branchId);
    }

    public function hasActiveBranch(): bool
    {
        return ! $this->isAllBranchesSelected() && (bool) $this->currentBranchId();
    }

    public function isAllBranchesSelected(): bool
    {
        return Session::get($this->sessionKey().'.all', false) === true;
    }

    public function setBranch(Branch|int $branch): void
    {
        $branchId = $branch instanceof Branch ? $branch->getKey() : $branch;
        $branchModel = $branch instanceof Branch ? $branch : Branch::query()->findOrFail($branchId);

        if (! $branchModel->isActive()) {
            throw new RuntimeException('Inactive branches cannot be selected.');
        }

        Session::put($this->sessionKey().'.id', $branchModel->getKey());
        Session::forget($this->sessionKey().'.all');
    }

    public function setAllBranches(): void
    {
        Session::put($this->sessionKey().'.all', true);
        Session::forget($this->sessionKey().'.id');
    }

    public function clearBranch(): void
    {
        Session::forget($this->sessionKey());
    }

    public function syncForUser(?User $user = null): void
    {
        $user ??= $this->currentUser();

        if (! $user) {
            $this->clearBranch();

            return;
        }

        if ($user->isSuperAdministrator()) {
            if (! Session::has($this->sessionKey().'.id') && ! $this->isAllBranchesSelected()) {
                $this->setAllBranches();
            }

            $this->pruneInactiveSelection();

            return;
        }

        // Resolved directly (not via setBranch()) because a branch that has
        // gone inactive since login must degrade to "no active branch"
        // rather than throw — the user cannot self-serve a new selection.
        if ($user->branch_id && $user->branch?->isActive()) {
            Session::put($this->sessionKey().'.id', $user->branch_id);
            Session::forget($this->sessionKey().'.all');
        } else {
            $this->clearBranch();
        }
    }

    public function pruneInactiveSelection(): void
    {
        if ($this->isAllBranchesSelected()) {
            return;
        }

        $branchId = Session::get($this->sessionKey().'.id');

        if (! $branchId) {
            return;
        }

        $branch = Branch::query()->find($branchId);

        if (! $branch || ! $branch->isActive()) {
            $this->clearBranch();
        }
    }

    public function withoutBranchContext(callable $callback): mixed
    {
        $state = Session::get($this->sessionKey());

        $this->clearBranch();
        Session::put($this->suspendedSessionKey(), true);

        try {
            return $callback();
        } finally {
            Session::forget($this->suspendedSessionKey());
            Session::put($this->sessionKey(), $state);
        }
    }

    public function isBypassed(): bool
    {
        return Session::get($this->suspendedSessionKey(), false) === true;
    }

    protected function suspendedSessionKey(): string
    {
        return $this->sessionKey().'_suspended';
    }

    public function requireSpecificBranch(): void
    {
        if ($this->isAllBranchesSelected() || ! $this->currentBranchId()) {
            throw ValidationException::withMessages([
                'branch_selection' => 'A specific active branch is required for this action.',
            ]);
        }
    }

    public function sessionKey(): string
    {
        return config('hrms.branch_context_session_key');
    }
}
