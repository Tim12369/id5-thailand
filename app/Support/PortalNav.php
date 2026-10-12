<?php

namespace App\Support;

class PortalNav
{
    /**
     * Full navigation for the desktop sidebar.
     *
     * Each entry is either:
     *   ['type' => 'link',  'route' => ..., 'label' => ..., 'icon' => ...]
     *   ['type' => 'group', 'label' => ..., 'icon' => ..., 'children' => [['route' => ..., 'label' => ...], ...]]
     *
     * Website content is no longer edited here — it is authored in WordPress
     * and synced in by App\Services\Cms. The "Website Content" entry below is
     * therefore a signpost rather than a screen: it sends staff straight to
     * the WordPress admin, so nobody has to remember a second address.
     *
     * What is left is the work WordPress has no business doing: operational
     * statistics, real-time operations, the accounts that authorise them, and
     * the audit log.
     */
    public static function items(): array
    {
        return [
            ['type' => 'link', 'route' => 'internal.executive.index', 'label' => 'Executive Dashboard', 'icon' => 'ti-layout-dashboard'],
            ['type' => 'link', 'route' => 'internal.command-center.index', 'label' => 'Command Center', 'icon' => 'ti-command'],

            ['type' => 'group', 'label' => 'Users & Permissions', 'icon' => 'ti-users-group', 'children' => [
                ['route' => 'internal.users.index', 'label' => 'Users'],
                ['route' => 'internal.roles.index', 'label' => 'Roles'],
                ['route' => 'internal.permissions.index', 'label' => 'Permissions'],
            ]],

            ['type' => 'link', 'route' => 'internal.audit-logs.index', 'label' => 'Audit Logs', 'icon' => 'ti-history'],
        ];
    }

    /**
     * The WordPress admin, when one is configured.
     *
     * Returned separately from items() because it leaves the application: the
     * sidebar renders it as an external link rather than a portal route.
     *
     * @return array{url: string, label: string, icon: string}|null
     */
    public static function websiteContentLink(): ?array
    {
        $url = (string) config('cms.base_url');

        if ($url === '' || ! config('cms.enabled')) {
            return null;
        }

        return [
            'url'   => $url.'/wp-admin/',
            'label' => 'Website Content',
            'icon'  => 'ti-world',
        ];
    }

    /**
     * Every route name that appears in items() -- used to highlight the
     * current group as active/expanded when a child route is active.
     */
    public static function isGroupActive(array $group): bool
    {
        foreach ($group['children'] as $child) {
            if (request()->routeIs($child['route'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Primary mobile bottom-tab items (4) plus a trailing "More" trigger.
     */
    public static function mobileItems(): array
    {
        return [
            ['route' => 'internal.executive.index', 'label' => 'Home', 'icon' => 'ti-layout-dashboard'],
            ['route' => 'internal.command-center.index', 'label' => 'Command', 'icon' => 'ti-command'],
            ['route' => 'internal.users.index', 'label' => 'Users', 'icon' => 'ti-users-group'],
            ['route' => 'internal.audit-logs.index', 'label' => 'Audit', 'icon' => 'ti-history'],
            ['route' => 'more', 'label' => 'More', 'icon' => 'ti-grid-dots'],
        ];
    }

    /**
     * Remaining modules surfaced in the mobile "More" overflow sheet.
     */
    public static function overflowItems(): array
    {
        return [
            ['route' => 'internal.roles.index', 'label' => 'Roles', 'icon' => 'ti-shield-check'],
            ['route' => 'internal.permissions.index', 'label' => 'Permissions', 'icon' => 'ti-key'],
        ];
    }
}
