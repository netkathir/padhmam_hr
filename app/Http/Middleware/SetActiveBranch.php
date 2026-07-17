<?php

namespace App\Http\Middleware;

use App\Services\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveBranch
{
    public function __construct(private readonly BranchContext $branchContext)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            $this->branchContext->clearBranch();

            return $next($request);
        }

        $this->branchContext->syncForUser($request->user());

        return $next($request);
    }
}
