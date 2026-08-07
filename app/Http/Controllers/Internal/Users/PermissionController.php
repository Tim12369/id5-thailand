<?php

namespace App\Http\Controllers\Internal\Users;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    /**
     * Permissions are code-defined (see database/seeders/PermissionSeeder.php)
     * rather than freely creatable through the UI -- each one is tied to a
     * specific module's `.view`/`.manage` gate in a controller, so creating
     * one here without matching code would be a permission that does
     * nothing. This screen is a read-only reference of what exists and
     * which roles currently have it, with role editing linked from here.
     */
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('users.view'), 403);

        $permissionsByModule = Permission::with('roles')
            ->orderBy('module')
            ->orderBy('slug')
            ->get()
            ->groupBy('module');

        return view('internal.users.permissions.index', compact('permissionsByModule'));
    }
}
