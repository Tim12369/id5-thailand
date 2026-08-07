<?php

namespace Database\Seeders;

use App\Models\OrgChartMember;
use App\Models\OrgChartSection;
use App\Models\OrgChartTier;
use Illuminate\Database\Seeder;

class OrgChartSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'slug' => 'police', 'name_th' => 'สำนักงานตำรวจแห่งชาติ', 'name_en' => 'Royal Thai Police',
                'theme' => 'red_gold', 'sort_order' => 1,
                'tiers' => [
                    ['label_th' => 'ผู้บัญชาการตำรวจแห่งชาติ', 'count' => 1],
                    ['label_th' => 'รองผู้บัญชาการตำรวจแห่งชาติ', 'count' => 7],
                    ['label_th' => 'ที่ปรึกษาพิเศษ สำนักงานตำรวจแห่งชาติ', 'count' => 2],
                    ['label_th' => 'ผู้ช่วยผู้บัญชาการตำรวจแห่งชาติ', 'count' => 9],
                    ['label_th' => 'รองผู้บัญชาการ และผู้บังคับการ', 'count' => 16],
                    ['label_th' => 'ผู้บัญชาการตำรวจภูธรภาค', 'count' => 9],
                    ['label_th' => 'ผู้บังคับการประจำกองบัญชาการ', 'count' => 13],
                ],
            ],
            [
                'slug' => 'immigration_bureau', 'name_th' => 'สำนักงานตรวจคนเข้าเมือง', 'name_en' => 'Immigration Bureau',
                'theme' => 'navy', 'sort_order' => 2,
                'tiers' => [
                    ['label_th' => 'ผู้บัญชาการ', 'count' => 1],
                    ['label_th' => 'รองผู้บัญชาการ', 'count' => 5],
                    ['label_th' => 'ผู้บังคับการ', 'count' => 10],
                ],
            ],
            [
                'slug' => 'division5', 'name_th' => 'กองบังคับการตรวจคนเข้าเมือง 5', 'name_en' => 'Immigration Division 5',
                'theme' => 'navy_gold', 'sort_order' => 3,
                'tiers' => [
                    ['label_th' => 'ผู้บังคับบัญชา', 'count' => 1],
                    ['label_th' => 'รองผู้บังคับการ', 'count' => 4],
                    ['label_th' => 'ผู้กำกับการ', 'count' => 10],
                    ['label_th' => 'ระดับสารวัตร (หัวหน้า ตม.จว.)', 'count' => 8],
                ],
            ],
            [
                // The 4 sections below each get their own dedicated page
                // (/technology-division, /administrative-affairs-division, etc.)
                // instead of a tab on About Us -- see show_on_about_page below,
                // AboutController, and DivisionPageController.
                'slug' => 'technology_division', 'name_th' => 'ฝ่ายเทคโนโลยีตรวจคนเข้าเมือง', 'name_en' => 'Immigration Technology Division',
                'theme' => 'navy', 'sort_order' => 4, 'show_on_about_page' => false,
                'tiers' => [
                    ['label_th' => 'หัวหน้าฝ่ายเทคโนโลยีตรวจคนเข้าเมือง', 'count' => 1],
                    ['label_th' => 'เจ้าหน้าที่เทคโนโลยีสารสนเทศ', 'count' => 4],
                ],
            ],
            [
                'slug' => 'administration_division', 'name_th' => 'ฝ่ายอำนวยการ', 'name_en' => 'Administrative Affairs Division',
                'theme' => 'navy', 'sort_order' => 5, 'show_on_about_page' => false,
                'tiers' => [
                    ['label_th' => 'หัวหน้าฝ่ายอำนวยการ', 'count' => 1],
                    ['label_th' => 'เจ้าหน้าที่ธุรการ', 'count' => 4],
                ],
            ],
            [
                'slug' => 'foreigner_division', 'name_th' => 'ฝ่ายบริการคนต่างด้าว', 'name_en' => 'Foreigner Services Division',
                'theme' => 'navy', 'sort_order' => 6, 'show_on_about_page' => false,
                'tiers' => [
                    ['label_th' => 'หัวหน้าฝ่ายบริการคนต่างด้าว', 'count' => 1],
                    ['label_th' => 'เจ้าหน้าที่บริการคนต่างด้าว', 'count' => 4],
                ],
            ],
            [
                'slug' => 'investigation_division', 'name_th' => 'ฝ่ายสืบสวนสอบสวน', 'name_en' => 'Investigation Division',
                'theme' => 'navy', 'sort_order' => 7, 'show_on_about_page' => false,
                'tiers' => [
                    ['label_th' => 'หัวหน้าฝ่ายสืบสวนสอบสวน', 'count' => 1],
                    ['label_th' => 'เจ้าหน้าที่สืบสวนสอบสวน', 'count' => 4],
                ],
            ],
        ];

        $sectionCount = 0;
        $tierCount = 0;
        $memberCount = 0;

        foreach ($sections as $sectionDef) {
            $section = OrgChartSection::updateOrCreate(
                ['slug' => $sectionDef['slug']],
                [
                    'name_th' => $sectionDef['name_th'],
                    'name_en' => $sectionDef['name_en'],
                    'theme' => $sectionDef['theme'],
                    'sort_order' => $sectionDef['sort_order'],
                    'show_on_about_page' => $sectionDef['show_on_about_page'] ?? true,
                ]
            );
            $sectionCount++;

            foreach ($sectionDef['tiers'] as $tierIndex => $tierDef) {
                $tier = OrgChartTier::firstOrCreate(
                    ['section_id' => $section->id, 'label_th' => $tierDef['label_th']],
                    ['sort_order' => $tierIndex + 1]
                );
                $tierCount++;

                if ($tier->members()->count() === 0) {
                    for ($i = 1; $i <= $tierDef['count']; $i++) {
                        OrgChartMember::create([
                            'tier_id' => $tier->id,
                            'name_th' => 'ตำแหน่งว่าง',
                            'rank_th' => null,
                            'position_th' => $tierDef['label_th'],
                            'photo_path' => null,
                            'sort_order' => $i,
                        ]);
                        $memberCount++;
                    }
                }
            }
        }

        $this->command->info("✅ Org Chart seeded: {$sectionCount} sections, {$tierCount} tiers, {$memberCount} placeholder members");
    }
}
