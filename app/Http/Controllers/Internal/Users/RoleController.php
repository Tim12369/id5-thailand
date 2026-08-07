<?php

namespace App\Http\Controllers\Internal\Users;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('users.view'), 403);

        $roles = Role::withCount(['users', 'permissions'])->orderBy('name')->get();

        return view('internal.users.roles.index', compact('roles'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        return view('internal.users.roles.form', [
            'role' => null,
            'permissionsByModule' => $this->permissionsByModule(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        $data = $this->validateRole($request);

        $role = Role::create($data['fields']);
        $role->permissions()->sync($data['permission_ids']);

        return redirect()->route('internal.roles.index')->with('success', 'Role created.');
    }

    public function edit(Request $request, Role $role): View
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        return view('internal.users.roles.form', [
            'role' => $role->load('permissions'),
            'permissionsByModule' => $this->permissionsByModule(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        $data = $this->validateRole($request, ignoreRoleId: $role->id);

        $role->update($data['fields']);
        $role->permissions()->sync($data['permission_ids']);

        return redirect()->route('internal.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Cannot delete a role that still has users assigned to it.']);
        }

        $role->delete();

        return redirect()->route('internal.roles.index')->with('success', 'Role deleted.');
    }

    private function permissionsByModule()
    {
        return Permission::orderBy('module')->orderBy('slug')->get()->groupBy('module');
    }

    /**
     * @return array{fields: array<string, mixed>, permission_ids: array<int>}
     */
    private function validateRole(Request $request, ?int $ignoreRoleId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:roles,slug' . ($ignoreRoleId ? ",{$ignoreRoleId}" : '')],
            'description' => ['nullable', 'string', 'max:1000'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        return [
            'fields' => [
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
            ],
            'permission_ids' => $validated['permission_ids'] ?? [],
        ];
    }
}
