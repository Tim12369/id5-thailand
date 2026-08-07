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
     * Operational tools (Executive Dashboard, Command Center) stay top-level
     * links rather than being folded into a group -- they're real-time/
     * analytics tools, not website content, and don't fit naturally under
     * Website Management, etc.
     */
    public static function items(): array
    {
        return [
            ['type' => 'link', 'route' => 'internal.executive.index', 'label' => 'Executive Dashboard', 'icon' => 'ti-layout-dashboard'],
            ['type' => 'link', 'route' => 'internal.command-center.index', 'label' => 'Command Center', 'icon' => 'ti-command'],

            ['type' => 'group', 'label' => 'Website Management', 'icon' => 'ti-world', 'children' => [
                ['route' => 'internal.website-homepage.index', 'label' => 'Homepage'],
                ['route' => 'internal.settings.edit', 'label' => 'Hero Images'],
                ['route' => 'internal.website-divisions.index', 'label' => 'Homepage Divisions'],
                ['route' => 'internal.cms.announcements.index', 'label' => 'Announcements'],
                ['route' => 'internal.cms.news.index', 'label' => 'News'],
                ['route' => 'internal.cms.alerts.index', 'label' => 'Alerts'],
                ['route' => 'internal.cms.knowledge-base.index', 'label' => 'Knowledge Base'],
                ['route' => 'internal.faq.index', 'label' => 'FAQ'],
                ['route' => 'internal.ita.index', 'label' => 'ITA'],
                ['route' => 'internal.org-chart.index', 'label' => 'Organization Chart'],
                ['route' => 'internal.website-contact.index', 'label' => 'Contact Information'],
                ['route' => 'internal.website-forms.index', 'label' => 'Download Forms'],
                ['route' => 'internal.website-about-documents.index', 'label' => 'About Us Documents'],
            ]],

            ['type' => 'group', 'label' => 'Smart Guide', 'icon' => 'ti-compass', 'children' => [
                ['route' => 'internal.smart-guide-topics.index', 'label' => 'Topics'],
                ['route' => 'internal.smart-guide-content.index', 'label' => 'Content (SW1H)'],
                ['route' => 'internal.assessment-content.index', 'label' => 'Assessment Results'],
            ]],

            ['type' => 'link', 'route' => 'internal.media-library.index', 'label' => 'Media Library', 'icon' => 'ti-photo-video'],

            ['type' => 'group', 'label' => 'Users & Permissions', 'icon' => 'ti-users-group', 'children' => [
                ['route' => 'internal.users.index', 'label' => 'Users'],
                ['route' => 'internal.roles.index', 'label' => 'Roles'],
                ['route' => 'internal.permissions.index', 'label' => 'Permissions'],
            ]],

            ['type' => 'group', 'label' => 'Settings', 'icon' => 'ti-settings', 'children' => [
                ['route' => 'internal.website-settings.index', 'label' => 'Website Settings'],
                ['route' => 'internal.appearance.index', 'label' => 'Appearance'],
                ['route' => 'internal.languages.index', 'label' => 'Languages'],
                ['route' => 'internal.audit-logs.index', 'label' => 'Audit Logs'],
            ]],
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
            ['route' => 'internal.website-homepage.index', 'label' => 'Website', 'icon' => 'ti-world'],
            ['route' => 'internal.media-library.index', 'label' => 'Media', 'icon' => 'ti-photo-video'],
            ['route' => 'more', 'label' => 'More', 'icon' => 'ti-grid-dots'],
        ];
    }

    /**
     * Remaining modules surfaced in the mobile "More" overflow sheet.
     */
    public static function overflowItems(): array
    {
        return [
            ['route' => 'internal.cms.announcements.index', 'label' => 'Announcements', 'icon' => 'ti-speakerphone'],
            ['route' => 'internal.cms.news.index', 'label' => 'News', 'icon' => 'ti-news'],
            ['route' => 'internal.ita.index', 'label' => 'ITA', 'icon' => 'ti-shield-check'],
            ['route' => 'internal.users.index', 'label' => 'Users', 'icon' => 'ti-users-group'],
            ['route' => 'internal.website-settings.index', 'label' => 'Settings', 'icon' => 'ti-settings'],
        ];
    }
}
