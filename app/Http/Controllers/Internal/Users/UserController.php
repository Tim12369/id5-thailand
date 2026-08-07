<?php

namespace App\Http\Controllers\Internal\Users;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Station;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('users.view'), 403);

        $users = User::with(['roles', 'station'])->orderBy('name')->paginate(20);

        return view('internal.users.index', compact('users'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        return view('internal.users.form', [
            'targetUser' => null,
            'roles' => Role::orderBy('name')->get(),
            'stations' => Station::orderBy('name_th')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        $data = $this->validateUser($request, requirePassword: true);

        $user = User::create([
            ...$data['fields'],
            'password' => Hash::make($data['password']),
        ]);

        $user->roles()->sync($data['role_ids']);

        return redirect()->route('internal.users.index')->with('success', 'User created.');
    }

    public function edit(Request $request, User $user): View
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        return view('internal.users.form', [
            'targetUser' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(),
            'stations' => Station::orderBy('name_th')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        $data = $this->validateUser($request, requirePassword: false, ignoreUserId: $user->id);

        $user->update($data['fields']);

        if ($data['password']) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->roles()->sync($data['role_ids']);

        return redirect()->route('internal.users.index')->with('success', 'User updated.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('users.manage'), 403);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot disable your own account.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'User enabled.' : 'User disabled.');
    }

    /**
     * @return array{fields: array<string, mixed>, password: ?string, role_ids: array<int>}
     */
    private function validateUser(Request $request, bool $requirePassword, ?int $ignoreUserId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email' . ($ignoreUserId ? ",{$ignoreUserId}" : '')],
            'employee_id' => ['nullable', 'string', 'max:50', 'unique:users,employee_id' . ($ignoreUserId ? ",{$ignoreUserId}" : '')],
            'position' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'station_id' => ['nullable', 'exists:stations,id'],
            'password' => [$requirePassword ? 'required' : 'nullable', 'string', 'min:8'],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        return [
            'fields' => [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'employee_id' => $validated['employee_id'] ?? null,
                'position' => $validated['position'] ?? null,
                'department' => $validated['department'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'station_id' => $validated['station_id'] ?? null,
            ],
            'password' => $validated['password'] ?? null,
            'role_ids' => $validated['role_ids'],
        ];
    }
}
