<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Modules the Internal Portal still gates.
     *
     * Website content is no longer among them: it is authored in WordPress,
     * which has its own roles and capabilities (see the "ผู้ดูแลเว็บไซต์"
     * role created by the id5-cms plugin). The permissions that covered the
     * removed CMS screens — cms, announcements, org-chart, ita, media,
     * website, smart-guide — are revoked below rather than left dangling,
     * so a leftover grant can never imply access nobody can exercise.
     */
    private const MODULES = [
        'dashboard' => 'Portal Home',
        'executive-dashboard' => 'Executive Dashboard',
        'command-center' => 'Command Center',
        'settings' => 'Portal Settings',
        'users' => 'Users & Permissions',
    ];

    /**
     * Modules that existed only to gate the old content-management screens.
     */
    private const RETIRED_MODULES = [
        'cms', 'announcements', 'org-chart', 'ita', 'media', 'website', 'smart-guide',
    ];

    public function run(): void
    {
        foreach (self::MODULES as $slug => $label) {
            Permission::updateOrCreate(
                ['slug' => "{$slug}.view"],
                ['name' => "View {$label}", 'module' => $slug, 'description' => "View access to {$label}"]
            );

            Permission::updateOrCreate(
                ['slug' => "{$slug}.manage"],
                ['name' => "Manage {$label}", 'module' => $slug, 'description' => "Manage access to {$label}"]
            );
        }

        // Retire the permissions behind the removed screens. Detaching before
        // deleting keeps the pivot clean on databases without cascade rules.
        $retired = Permission::whereIn('module', self::RETIRED_MODULES)->get();

        foreach ($retired as $permission) {
            $permission->roles()->detach();
            $permission->delete();
        }

        $permissionIds = Permission::pluck('id');
        $roles = Role::all();

        foreach ($roles as $role) {
            $role->permissions()->sync($permissionIds);
        }

        // Command Center is an operational/security-sensitive module — only
        // Administrators may change incident status. Everyone else (Executive,
        // Staff Officer, Investigation/Suppression/Checkpoint/IT Officer) keeps
        // command-center.view (granted above) but not .manage.
        $manage = Permission::where('slug', 'command-center.manage')->first();

        if ($manage) {
            foreach ($roles as $role) {
                if ($role->slug !== 'administrator') {
                    $role->permissions()->detach($manage->id);
                }
            }
        }

        // Portal Settings covers the audit log, and Users & Permissions
        // controls who can log in at all and what every role can do — restrict
        // both viewing and managing to Administrators only.
        $adminOnlyPermissionIds = Permission::whereIn('module', ['settings', 'users'])->pluck('id');

        foreach ($roles as $role) {
            if ($role->slug !== 'administrator') {
                $role->permissions()->detach($adminOnlyPermissionIds);
            }
        }
    }
}
