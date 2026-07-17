<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $old = ['password_changed_at' => $user->password_changed_at];

        $user->forceFill([
            'password' => Hash::make((string) $request->string('password')),
            'password_changed_at' => now(),
        ])->save();

        $this->auditService->record('password_change', 'authentication', $user, $old, [
            'user_id' => $user->id,
            'password_changed_at' => $user->password_changed_at,
        ], $request);

        return back()->with('status', 'Password updated successfully.');
    }
}
