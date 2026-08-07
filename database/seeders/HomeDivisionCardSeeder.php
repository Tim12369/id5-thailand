<?php

namespace Database\Seeders;

use App\Models\HomeDivisionCard;
use Illuminate\Database\Seeder;

class HomeDivisionCardSeeder extends Seeder
{
    public function run(): void
    {
        $cards = [
            [
                'key' => 'who_are_you',
                'title_th' => 'คุณคือใคร?',
                'title_en' => 'Who Are You?',
                'desc_th' => 'เลือกประเภทผู้ใช้งาน',
                'desc_en' => 'Choose your user type',
                'sort_order' => 1,
            ],
            [
                'key' => 'where_are_you',
                'title_th' => 'คุณอยู่ที่ไหน?',
                'title_en' => 'Where Are You?',
                'desc_th' => 'ค้นหาสำนักงานตรวจคนเข้าเมือง',
                'desc_en' => 'Find an immigration office',
                'sort_order' => 2,
            ],
            [
                'key' => 'technology',
                'title_th' => 'ฝ่ายเทคโนโลยีตรวจคนเข้าเมือง',
                'title_en' => 'Immigration Technology Division',
                'desc_th' => 'ระบบเทคโนโลยีและสารสนเทศ',
                'desc_en' => 'Technology and information systems',
                'sort_order' => 3,
            ],
            [
                'key' => 'administration',
                'title_th' => 'ฝ่ายอำนวยการ',
                'title_en' => 'Administrative Affairs Division',
                'desc_th' => 'ข้อมูล ข่าวสาร และบริการของฝ่ายอำนวยการ',
                'desc_en' => 'Information, news, and services from the Administrative Affairs Division',
                'sort_order' => 4,
            ],
            [
                'key' => 'foreigner',
                'title_th' => 'ฝ่ายบริการคนต่างด้าว',
                'title_en' => 'Foreigner Services Division',
                'desc_th' => 'ข้อมูลและบริการสำหรับชาวต่างชาติ',
                'desc_en' => 'Information and services for foreign nationals',
                'sort_order' => 5,
            ],
            [
                'key' => 'investigation',
                'title_th' => 'ฝ่ายสืบสวนสอบสวน',
                'title_en' => 'Investigation Division',
                'desc_th' => 'งานสืบสวนและการบังคับใช้กฎหมาย',
                'desc_en' => 'Investigation and law enforcement operations',
                'sort_order' => 6,
            ],
        ];

        foreach ($cards as $data) {
            HomeDivisionCard::updateOrCreate(['key' => $data['key']], $data);
        }

        $this->command->info('✅ Home division cards seeded: ' . count($cards));
    }
}
