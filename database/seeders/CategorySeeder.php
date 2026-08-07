<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // News categories
            ['type' => 'news', 'name_th' => 'ข่าวประชาสัมพันธ์',     'name_en' => 'Press Release',      'slug' => 'press-release',    'icon' => 'ti-news',        'sort_order' => 1],
            ['type' => 'news', 'name_th' => 'ข่าวนโยบาย',              'name_en' => 'Policy News',        'slug' => 'policy',           'icon' => 'ti-file-text',   'sort_order' => 2],
            ['type' => 'news', 'name_th' => 'ข่าวปฏิบัติการ',          'name_en' => 'Operations',         'slug' => 'operations',       'icon' => 'ti-shield',      'sort_order' => 3],

            // Public KB categories
            ['type' => 'kb_public', 'name_th' => 'วีซ่า',              'name_en' => 'Visa',               'slug' => 'visa',             'icon' => 'ti-id',          'sort_order' => 1],
            ['type' => 'kb_public', 'name_th' => 'TM30',               'name_en' => 'TM30',               'slug' => 'tm30',             'icon' => 'ti-file-text',   'sort_order' => 2],
            ['type' => 'kb_public', 'name_th' => 'รายงานตัว 90 วัน',  'name_en' => '90-Day Report',      'slug' => '90-day',           'icon' => 'ti-calendar',    'sort_order' => 3],
            ['type' => 'kb_public', 'name_th' => 'ต่ออายุ',            'name_en' => 'Extension',          'slug' => 'extension',        'icon' => 'ti-refresh',     'sort_order' => 4],
            ['type' => 'kb_public', 'name_th' => 'อยู่เกินกำหนด',      'name_en' => 'Overstay',           'slug' => 'overstay',         'icon' => 'ti-alert-triangle','sort_order' => 5],

            // FAQ categories
            ['type' => 'faq', 'name_th' => 'วีซ่า',                    'name_en' => 'Visa',               'slug' => 'visa-faq',         'icon' => 'ti-id',          'sort_order' => 1],
            ['type' => 'faq', 'name_th' => 'TM30',                     'name_en' => 'TM30',               'slug' => 'tm30-faq',         'icon' => 'ti-file-text',   'sort_order' => 2],
            ['type' => 'faq', 'name_th' => 'รายงานตัว',               'name_en' => 'Reporting',          'slug' => 'reporting-faq',    'icon' => 'ti-calendar',    'sort_order' => 3],
            ['type' => 'faq', 'name_th' => 'ต่ออายุ',                  'name_en' => 'Extension',          'slug' => 'extension-faq',    'icon' => 'ti-refresh',     'sort_order' => 4],
            ['type' => 'faq', 'name_th' => 'ทั่วไป',                   'name_en' => 'General',            'slug' => 'general-faq',      'icon' => 'ti-help',        'sort_order' => 5],

            // Form categories (for public_forms display grouping)
            ['type' => 'form', 'name_th' => 'แบบฟอร์มวีซ่า',          'name_en' => 'Visa Forms',         'slug' => 'visa-forms',       'icon' => 'ti-id',          'sort_order' => 1],
            ['type' => 'form', 'name_th' => 'แบบฟอร์มรายงานตัว',      'name_en' => 'Reporting Forms',    'slug' => 'reporting-forms',  'icon' => 'ti-file',        'sort_order' => 2],
            ['type' => 'form', 'name_th' => 'แบบฟอร์มต่ออายุ',        'name_en' => 'Extension Forms',   'slug' => 'extension-forms',  'icon' => 'ti-refresh',     'sort_order' => 3],

            // SOP categories (internal)
            ['type' => 'sop', 'name_th' => 'กระบวนการตรวจสอบ',        'name_en' => 'Inspection Process', 'slug' => 'inspection',       'icon' => 'ti-list-check',  'sort_order' => 1],
            ['type' => 'sop', 'name_th' => 'การให้บริการประชาชน',       'name_en' => 'Public Service',     'slug' => 'public-service',   'icon' => 'ti-users',       'sort_order' => 2],

            // Internal KB
            ['type' => 'kb_internal', 'name_th' => 'ระบบสารสนเทศ',    'name_en' => 'IT Systems',         'slug' => 'it-systems',       'icon' => 'ti-device-desktop','sort_order' => 1],
            ['type' => 'kb_internal', 'name_th' => 'Best Practice',    'name_en' => 'Best Practice',      'slug' => 'best-practice',    'icon' => 'ti-star',         'sort_order' => 2],
        ];

        foreach ($categories as $data) {
            Category::updateOrCreate(
                ['type' => $data['type'], 'slug' => $data['slug']],
                array_merge($data, ['is_active' => true])
            );
        }

        $this->command->info('✅ Categories seeded: ' . count($categories));
    }
}
