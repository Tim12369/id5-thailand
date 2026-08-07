<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'dashboard' => 'Portal Home',
            'executive-dashboard' => 'Executive Dashboard',
            'cms' => 'Content Management',
            'command-center' => 'Command Center',
            'announcements' => 'Internal Announcements',
            'settings' => 'Site Settings',
            'org-chart' => 'Organization Chart',
            'ita' => 'ITA Management',
            'users' => 'Users & Permissions',
            'media' => 'Media Library',
            'website' => 'Website Management',
            'smart-guide' => 'Smart Guide',
        ];

        foreach ($modules as $slug => $label) {
            Permission::updateOrCreate(
                ['slug' => "{$slug}.view"],
                ['name' => "View {$label}", 'module' => $slug, 'description' => "View access to {$label}"]
            );

            Permission::updateOrCreate(
                ['slug' => "{$slug}.manage"],
                ['name' => "Manage {$label}", 'module' => $slug, 'description' => "Manage access to {$label}"]
            );
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
        //
        // ITA content is public-facing compliance material — anyone with portal
        // access may view the management screen, but only Administrators may
        // edit/publish it, so it follows the same view-yes/manage-no pattern.
        $manageOnlyAdminSlugs = ['command-center.manage', 'ita.manage'];

        foreach ($manageOnlyAdminSlugs as $slug) {
            $manage = Permission::where('slug', $slug)->first();

            if ($manage) {
                foreach ($roles as $role) {
                    if ($role->slug !== 'administrator') {
                        $role->permissions()->detach($manage->id);
                    }
                }
            }
        }

        // Site Settings and Organization Chart control public-facing
        // branding/content (homepage hero image, leadership charts) — and
        // Users & Permissions controls who can log in at all and what every
        // role can do — restrict both viewing and managing all three to
        // Administrators only.
        $adminOnlyModules = ['settings', 'org-chart', 'users'];
        $adminOnlyPermissionIds = Permission::whereIn('module', $adminOnlyModules)->pluck('id');

        foreach ($roles as $role) {
            if ($role->slug !== 'administrator') {
                $role->permissions()->detach($adminOnlyPermissionIds);
            }
        }
    }
}
