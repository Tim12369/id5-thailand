<?php

namespace App\Support;

use App\Models\PageSection;
use Illuminate\Support\Collection;

/**
 * Places the content sections editors built in WordPress onto the website's
 * own pages (`@cmsSections('position')` in a template).
 *
 * A visitor sees nothing extra where no section has been placed: the slot
 * prints an empty string, so pages without sections are exactly as they were.
 * In edit mode each slot also offers "+ เพิ่มส่วนเนื้อหา", which opens a new
 * section in WordPress already set to this page and this position.
 */
class PageSections
{
    /** Positions between the homepage's own sections; elsewhere only the end. */
    public const HOME_POSITIONS = ['after_hero', 'after_services', 'after_news', 'bottom'];

    private const POSITION_NAMES = [
        'bottom'         => 'ท้ายหน้า',
        'after_hero'     => 'ใต้แบนเนอร์ใหญ่',
        'after_services' => 'ใต้การ์ดบริการ',
        'after_news'     => 'ใต้ข่าวสาร ประกาศ และแจ้งเตือน',
    ];

    /** One query per request for all of a page's sections. */
    private static ?array $loaded = null;

    public static function render(string $position): string
    {
        $path = self::currentPath();
        $home = $path === '/';

        if (! $home && $position !== 'bottom') {
            return '';
        }

        // Elsewhere than the homepage every section goes at the end, whatever
        // homepage position it was given.
        $sections = self::forPath($path)->filter(fn (PageSection $s) => ! $home || $s->position === $position || ($position === 'bottom' && ! in_array($s->position, self::HOME_POSITIONS, true)));

        $html = '';

        foreach ($sections as $section) {
            $html .= sprintf(
                '<section class="cms-section cms-section--%s">%s<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-12"><div class="prose cms-blocks max-w-none">%s</div></div></section>',
                e($section->background),
                CmsEdit::marker($section),
                $section->body_html
            );
        }

        if (CmsEdit::active() && in_array($path, (array) config('cms.section_pages', []), true)) {
            $html .= '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">'
                .CmsEdit::addTile(
                    'new:id5_section?'.http_build_query(['id5_page' => $path, 'id5_position' => $position], '', '&', PHP_QUERY_RFC3986),
                    'เพิ่มส่วนเนื้อหา (แถว คอลัมน์ รูป ข้อความ PDF)',
                    self::POSITION_NAMES[$position] ?? $position,
                    true
                )
                .'</div>';
        }

        return $html;
    }

    /** The published sections of one page, in order, for the current language. */
    public static function forPath(string $path): Collection
    {
        if (self::$loaded === null || self::$loaded['path'] !== $path) {
            $sections = rescue(
                fn () => PageSection::published()->forLocale()
                    ->where('kind', PageSection::SECTION)
                    ->where('page', $path)
                    ->orderBy('sort_order')->orderBy('id')
                    ->get(),
                collect(),
                report: false
            );

            self::$loaded = ['path' => $path, 'sections' => $sections];
        }

        return self::$loaded['sections'];
    }

    /** Forget what was loaded (a new request, or a test). */
    public static function reset(): void
    {
        self::$loaded = null;
    }

    private static function currentPath(): string
    {
        return '/'.trim(request()->path(), '/');
    }
}
