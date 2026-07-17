<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\UserStoreRequest;
use App\Http\Requests\Administration\UserUpdateRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\BranchContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly BranchContext $branchContext,
        private readonly AuditService $auditService,
    ) {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(): View
    {
        $users = User::query()->with(['branch', 'roles'])->latest()->paginate(10);

        return view('administration.users.index', compact('users'));
    }

    public function create(): View
    {
        $roles = $this->assignableRoles();

        return view('administration.users.create', compact('roles'));
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->branchContext->requireSpecificBranch();

        $user = User::create([
            'name' => (string) $request->string('name'),
            'username' => (string) $request->string('username'),
            'email' => (string) $request->string('email'),
            'phone' => $request->filled('phone') ? (string) $request->string('phone') : null,
            'status' => (string) $request->string('status'),
            'password' => Hash::make((string) $request->string('password')),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $user->roles()->sync($request->input('roles', []));

        $this->auditService->record('user_create', 'user', $user, [], [
            ...$user->toArray(),
            'roles' => $user->roles()->pluck('slug')->all(),
        ], $request);

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }

    public function show(User $user): View
    {
        $user->load(['branch', 'roles', 'permissions']);

        return view('administration.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $roles = $this->assignableRoles();

        return view('administration.users.edit', compact('user', 'roles'));
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $this->branchContext->requireSpecificBranch();

        $old = [
            ...$user->replicate()->toArray(),
            'roles' => $user->roles()->pluck('slug')->all(),
        ];

        $user->fill([
            'name' => (string) $request->string('name'),
            'username' => (string) $request->string('username'),
            'email' => (string) $request->string('email'),
            'phone' => $request->filled('phone') ? (string) $request->string('phone') : null,
            'status' => (string) $request->string('status'),
            'updated_by' => $request->user()->id,
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make((string) $request->string('password'));
            $user->password_changed_at = now();
        }

        $user->save();
        $user->roles()->sync($request->input('roles', []));

        $this->auditService->record('user_update', 'user', $user, $old, [
            ...$user->fresh()->toArray(),
            'roles' => $user->roles()->pluck('slug')->all(),
        ], $request);

        return redirect()->route('users.index')->with('status', 'User updated successfully.');
    }

    private function assignableRoles(): Collection
    {
        $query = Role::query()->where('status', 'active');

        if (! $this->branchContext->currentUser()?->isSuperAdministrator()) {
            $query->where('slug', '!=', 'super-administrator');
        }

        return $query->orderBy('name')->get();
    }
}
