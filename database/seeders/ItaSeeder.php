<?php

namespace Database\Seeders;

use App\Models\ItaCard;
use App\Models\ItaSection;
use Illuminate\Database\Seeder;

class ItaSeeder extends Seeder
{
    public function run(): void
    {
        $section = ItaSection::updateOrCreate(
            ['section_number' => '1'],
            [
                'title_th' => 'ค่านิยมหลักด้านคุณธรรมและความโปร่งใส',
                'title_en' => 'Core Integrity & Transparency Values',
                'description_th' => 'การประเมินคุณธรรมและความโปร่งใสในการดำเนินงานของหน่วยงานภาครัฐ โดยสำนักงาน ป.ป.ช.',
                'description_en' => 'Assessment of government agency integrity and transparency, conducted by the NACC.',
                'sort_order' => 1,
                'is_visible' => true,
            ]
        );

        $cards = [
            [
                'card_number' => '01',
                'title_th' => 'คุณธรรม',
                'title_en' => 'Integrity',
                'content_th' => 'การปฏิบัติหน้าที่ด้วยความซื่อสัตย์สุจริต ยึดมั่นในหลักคุณธรรมและจริยธรรมของเจ้าหน้าที่รัฐ',
                'content_en' => 'Carrying out duties with honesty, upholding ethical and moral standards expected of public officials.',
                'icon' => 'shield-check',
                'color' => '#4F46E5',
                'sort_order' => 1,
            ],
            [
                'card_number' => '02',
                'title_th' => 'ความโปร่งใส',
                'title_en' => 'Transparency',
                'content_th' => 'การเปิดเผยข้อมูลการดำเนินงานของหน่วยงานให้ประชาชนสามารถเข้าถึงและตรวจสอบได้',
                'content_en' => 'Disclosing agency operations so the public can access and scrutinize them.',
                'icon' => 'eye-check',
                'color' => '#0EA5E9',
                'sort_order' => 2,
            ],
            [
                'card_number' => '03',
                'title_th' => 'ต่อต้านการทุจริต',
                'title_en' => 'Anti-Corruption',
                'content_th' => 'มาตรการป้องกันและต่อต้านการทุจริตทุกรูปแบบภายในหน่วยงาน สอบถามเพิ่มเติมได้ที่สำนักงาน ป.ป.ช. (itas.nacc.go.th) หรือกองบังคับการตรวจคนเข้าเมือง (immigration.go.th)',
                'content_en' => 'Measures to prevent and combat all forms of corruption within the agency. See the NACC (itas.nacc.go.th) or the Immigration Bureau (immigration.go.th) for more.',
                'icon' => 'gavel',
                'color' => '#DC2626',
                'sort_order' => 3,
            ],
        ];

        foreach ($cards as $card) {
            ItaCard::updateOrCreate(
                ['section_id' => $section->id, 'card_number' => $card['card_number']],
                array_merge($card, ['section_id' => $section->id, 'is_visible' => true])
            );
        }
    }
}
