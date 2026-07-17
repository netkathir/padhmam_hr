<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly BranchContext $branchContext,
        private readonly AuditService $auditService,
    ) {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        /** @var User $user */
        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => 'Your account is inactive.',
            ]);
        }

        if (! $user->isSuperAdministrator()) {
            if (! $user->branch || ! $user->branch->isActive()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                throw ValidationException::withMessages([
                    'login' => 'Your assigned branch is inactive.',
                ]);
            }
        }

        $request->session()->regenerate();
        $this->branchContext->syncForUser($user);

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $this->auditService->record('login', 'authentication', $user, [], [
            'user_id' => $user->id,
            'branch_id' => $this->branchContext->currentBranchId(),
        ], $request);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $this->auditService->record('logout', 'authentication', $user, [], [
                'user_id' => $user->id,
            ], $request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->branchContext->clearBranch();

        return redirect()->route('login');
    }
}
