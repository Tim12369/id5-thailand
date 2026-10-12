<?php

namespace App\Support;

/**
 * Picks an icon for an ITA topic from its wording.
 *
 * An editor may upload a picture for a topic, or name an icon; most will do
 * neither. Rather than leave the topic bare, the page shows an icon that suits
 * what the topic is about: "งบประมาณ" gets a coin, "ร้องเรียน" a report
 * bubble, "โครงสร้าง" an organisation chart. Nothing is stored — the choice is
 * made when the page is drawn, so renaming a topic changes its icon with it.
 *
 * The rules are tried in order and the first whose keyword appears in the
 * topic's Thai or English title wins, so the more specific subjects come
 * first. Every icon named here exists in the site's icon font (there is a test
 * for exactly that).
 */
class ItaIcon
{
    /** Shown when no rule matches. */
    public const FALLBACK = 'file-text';

    /**
     * icon => keywords (lower case; Thai and English).
     *
     * @var array<string,string[]>
     */
    private const RULES = [
        // Integrity and anti-corruption — the heart of ITA
        'gift-off'          => ['no gift', 'ของขวัญ', 'ของกำนัล', 'สินบน', 'bribe'],
        'gavel'             => ['ทุจริต', 'ประพฤติมิชอบ', 'corruption', 'anti-corruption', 'วินัย', 'ลงโทษ', 'discipline'],
        'eye-check'         => ['โปร่งใส', 'transparen', 'เปิดเผย', 'disclos'],
        'shield-check'      => ['คุณธรรม', 'จริยธรรม', 'ธรรมาภิบาล', 'integrity', 'ethic', 'moral', 'governance', 'ซื่อสัตย์', 'สุจริต'],
        'alert-triangle'    => ['ความเสี่ยง', 'risk', 'เฝ้าระวัง', 'เตือน', 'warning'],
        'message-report'    => ['ร้องเรียน', 'ร้องทุกข์', 'แจ้งเบาะแส', 'complain', 'whistle', 'grievance'],

        // Money and procurement
        'shopping-cart'     => ['จัดซื้อ', 'จัดจ้าง', 'จัดหาพัสดุ', 'พัสดุ', 'procure', 'purchas', 'tender', 'ประกวดราคา'],
        'coin'              => ['งบประมาณ', 'การเงิน', 'ใช้จ่าย', 'รายจ่าย', 'รายรับ', 'ค่าธรรมเนียม', 'budget', 'financ', 'expenditure', 'spending', 'fee'],

        // People and organisation
        'sitemap'           => ['โครงสร้าง', 'ผังองค์กร', 'structure', 'organization chart', 'organisation chart', 'org chart'],
        'user-star'         => ['ผู้บริหาร', 'ผู้บังคับบัญชา', 'executive', 'commander', 'leadership', 'director'],
        'users'             => ['บุคลากร', 'ทรัพยากรบุคคล', 'เจ้าหน้าที่', 'กำลังพล', 'personnel', 'human resource', 'staff', 'officer', ' hr '],
        'school'            => ['ฝึกอบรม', 'อบรม', 'พัฒนาบุคลากร', 'training', 'capacity'],
        'heart-handshake'   => ['มีส่วนร่วม', 'participat', 'ภาคประชาชน', 'ชุมชน', 'community', 'engagement'],
        'briefcase'         => ['อำนาจหน้าที่', 'ภารกิจ', 'พันธกิจ', 'authority', 'mission', 'mandate', 'duties'],

        // Plans, results, numbers
        'target'            => ['ยุทธศาสตร์', 'วิสัยทัศน์', 'เป้าหมาย', 'strategy', 'strategic', 'vision', 'goal'],
        'calendar-event'    => ['แผนดำเนินงาน', 'แผนปฏิบัติ', 'แผนงาน', 'ปฏิทิน', 'action plan', 'operational plan', 'calendar', 'schedule'],
        'chart-bar'         => ['สถิติ', 'ตัวชี้วัด', 'statistic', 'indicator', 'kpi'],
        'file-analytics'    => ['รายงาน', 'ผลการดำเนินงาน', 'ผลการปฏิบัติ', 'ติดตาม', 'ประเมินผล', 'report', 'performance', 'result', 'evaluation', 'monitoring'],
        'mood-smile'        => ['ความพึงพอใจ', 'satisfaction', 'ความคิดเห็น', 'feedback', 'survey', 'สำรวจ'],
        'checklist'         => ['มาตรการ', 'มาตรฐาน', 'measure', 'standard', 'แนวปฏิบัติ', 'guideline', 'ข้อปฏิบัติ'],

        // Law, privacy, service, communication
        'scale'             => ['กฎหมาย', 'ระเบียบ', 'ข้อบังคับ', 'พระราชบัญญัติ', 'ประกาศ', 'law', 'regulation', 'legal', 'act '],
        'shield-lock'       => ['ข้อมูลส่วนบุคคล', 'ความเป็นส่วนตัว', 'ความมั่นคงปลอดภัย', 'privacy', 'personal data', 'pdpa', 'security'],
        'device-laptop'     => ['e-service', 'eservice', 'ออนไลน์', 'อิเล็กทรอนิกส์', 'ดิจิทัล', 'online', 'electronic', 'digital'],
        'book'              => ['คู่มือ', 'manual', 'handbook', 'ขั้นตอน', 'procedure'],
        'headset'           => ['การให้บริการ', 'บริการประชาชน', 'service', 'ช่องทางบริการ'],
        'message-question'  => ['q&a', 'ถาม-ตอบ', 'ถามตอบ', 'คำถาม', 'faq', 'question'],
        'share'             => ['social', 'โซเชียล', 'สื่อสังคม', 'เครือข่ายสังคม'],
        'speakerphone'      => ['ประชาสัมพันธ์', 'ข่าว', 'news', 'public relations', 'announcement'],
        'address-book'      => ['ติดต่อ', 'ที่อยู่', 'contact', 'address'],
        'database'          => ['ข้อมูลพื้นฐาน', 'ข้อมูลสาธารณะ', 'open data', 'ฐานข้อมูล', 'dataset'],
        'search'            => ['ตรวจสอบ', 'audit', 'inspect', 'ตรวจราชการ'],
        'clipboard-list'    => ['แผน', 'โครงการ', 'plan', 'project'],
        'info-circle'       => ['ข้อมูล', 'information'],
    ];

    /**
     * The icon for a topic described by any number of texts (titles first).
     */
    public static function suggest(?string ...$texts): string
    {
        $haystack = ' '.mb_strtolower(implode(' ', array_filter($texts))).' ';

        if (trim($haystack) === '') {
            return self::FALLBACK;
        }

        foreach (self::RULES as $icon => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    return $icon;
                }
            }
        }

        return self::FALLBACK;
    }

    /**
     * Turn what an editor typed into an icon name, or null when it is not one.
     *
     * Accepts the forms people actually type — "file-text", "ti-file-text",
     * "ti ti-file-text" — and rejects anything the icon font does not have, so
     * a typo falls back to the automatic choice instead of drawing nothing.
     */
    public static function normalise(?string $typed): ?string
    {
        $name = strtolower(trim((string) $typed));
        $name = preg_replace('/^(ti\s+)?ti-/', '', $name) ?? '';

        if ($name === '' || ! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $name)) {
            return null;
        }

        return in_array($name, self::available(), true) ? $name : null;
    }

    /**
     * Every icon the site's icon font can draw.
     *
     * @return string[]
     */
    public static function available(): array
    {
        static $names = null;

        if ($names === null) {
            $css = @file_get_contents(public_path('vendor/tabler-icons/tabler-icons.min.css')) ?: '';
            preg_match_all('/\.ti-([a-z0-9-]+):before/', $css, $m);
            $names = array_values(array_unique($m[1]));
        }

        return $names;
    }

    /**
     * The icons the rules can pick, for the test that checks the font has them.
     *
     * @return string[]
     */
    public static function candidates(): array
    {
        return array_merge(array_keys(self::RULES), [self::FALLBACK]);
    }
}
