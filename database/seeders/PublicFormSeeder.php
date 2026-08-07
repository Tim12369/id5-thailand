<?php

namespace Database\Seeders;

use App\Models\PublicForm;
use Illuminate\Database\Seeder;

class PublicFormSeeder extends Seeder
{
    public function run(): void
    {
        $forms = [
            [
                'code'           => 'TM30',
                'name_th'        => 'แบบแจ้งที่พักอาศัยของคนต่างด้าว',
                'name_en'        => 'Notification of Foreigner Taking up Residence',
                'description_th' => 'สำหรับเจ้าของที่พัก โรงแรม นายจ้าง แจ้งที่พักของคนต่างด้าว',
                'description_en' => 'For accommodation providers, hotels and employers to notify foreigner residence',
                'category'       => 'reporting',
                'path'           => 'forms/placeholder.pdf',
                'version'        => '1.0',
                'sort_order'     => 1,
            ],
            [
                'code'           => 'TM47',
                'name_th'        => 'แบบรายงานตัวอยู่ในราชอาณาจักรเกิน 90 วัน',
                'name_en'        => '90-Day Report Form',
                'description_th' => 'แบบฟอร์มรายงานตัวทุก 90 วัน สำหรับผู้ที่พำนักในไทยระยะยาว',
                'description_en' => 'For foreign nationals residing in Thailand to report every 90 days',
                'category'       => 'reporting',
                'path'           => 'forms/placeholder.pdf',
                'version'        => '1.0',
                'sort_order'     => 2,
            ],
            [
                'code'           => 'TM7',
                'name_th'        => 'แบบคำขออยู่ในราชอาณาจักรต่อไป',
                'name_en'        => 'Application for Extension of Temporary Stay',
                'description_th' => 'คำขอต่ออายุการพำนักในประเทศไทย',
                'description_en' => 'Application to extend your stay in Thailand',
                'category'       => 'extension',
                'path'           => 'forms/placeholder.pdf',
                'version'        => '1.0',
                'sort_order'     => 3,
            ],
            [
                'code'           => 'TM6',
                'name_th'        => 'บัตรขาเข้า/ขาออก (ยกเว้น ASEAN)',
                'name_en'        => 'Arrival/Departure Card (Non-ASEAN)',
                'description_th' => 'บัตรขาเข้าและขาออกสำหรับผู้ที่ไม่ใช่สัญชาติอาเซียน',
                'description_en' => 'Arrival and departure card for non-ASEAN nationals',
                'category'       => 'entry_exit',
                'path'           => 'forms/placeholder.pdf',
                'version'        => '1.0',
                'sort_order'     => 4,
            ],
            [
                'code'           => 'TM86',
                'name_th'        => 'แบบคำขอประทับตราอนุญาตให้พักอาศัยอยู่ในราชอาณาจักร',
                'name_en'        => 'Application for Residence Permit',
                'description_th' => 'คำขอรับใบอนุญาตพักอาศัยถาวร',
                'description_en' => 'Application for permanent residence in Thailand',
                'category'       => 'visa',
                'path'           => 'forms/placeholder.pdf',
                'version'        => '1.0',
                'sort_order'     => 5,
            ],
            [
                'code'           => 'TM87',
                'name_th'        => 'แบบคำขอมีสถานะเป็นคนต่างด้าวที่มีถิ่นที่อยู่ในราชอาณาจักร',
                'name_en'        => 'Application for Foreigner with Domicile',
                'description_th' => 'คำขอสถานะคนต่างด้าวที่มีถิ่นที่อยู่',
                'description_en' => 'Application for foreigner with domicile in Thailand',
                'category'       => 'visa',
                'path'           => 'forms/placeholder.pdf',
                'version'        => '1.0',
                'sort_order'     => 6,
            ],
            [
                'code'           => 'TM28',
                'name_th'        => 'แบบแจ้งการเข้าพักอาศัยในสถานที่ที่ไม่ใช่โรงแรม',
                'name_en'        => 'Report of Residence in Non-Hotel',
                'description_th' => 'สำหรับคนต่างด้าวที่พักในบ้านพักส่วนตัว',
                'description_en' => 'For foreigners residing in private accommodation',
                'category'       => 'reporting',
                'path'           => 'forms/placeholder.pdf',
                'version'        => '1.0',
                'sort_order'     => 7,
            ],
        ];

        foreach ($forms as $data) {
            PublicForm::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['is_active' => true, 'disk' => 'local'])
            );
        }

        $this->command->info('✅ Public forms seeded: ' . count($forms));
    }
}
