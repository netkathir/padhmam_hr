<?php

namespace App\Http\Middleware;

use App\Services\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveBranch
{
    public function __construct(private readonly BranchContext $branchContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdministrator()) {
            if ($this->isWriteRequest($request) && ! $this->branchContext->hasActiveBranch()) {
                abort(422, 'A specific active branch is required for this action.');
            }

            return $next($request);
        }

        if (! $this->branchContext->hasActiveBranch()) {
            abort(422, 'A specific active branch is required.');
        }

        if (! $user->branch_id || $user->branch_id !== $this->branchContext->currentBranchId()) {
            abort(403);
        }

        return $next($request);
    }

    private function isWriteRequest(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }
}
