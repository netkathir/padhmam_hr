<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\RoleUpdateRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
        $this->authorizeResource(Role::class, 'role');
    }

    public function index(): View
    {
        $roles = Role::query()->withCount('users')->latest()->paginate(10);

        return view('administration.roles.index', compact('roles'));
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group');
        $role->load('permissions');

        return view('administration.roles.edit', compact('role', 'permissions'));
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        $old = $role->load('permissions')->toArray();

        $role->permissions()->sync($request->input('permissions', []));

        $this->auditService->record('role_permissions_update', 'role', $role, $old, $role->fresh()->toArray(), $request);

        return redirect()->route('roles.index')->with('status', 'Role permissions updated successfully.');
    }
}
