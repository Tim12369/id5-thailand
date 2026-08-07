<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $visaCat    = Category::where('type', 'faq')->where('slug', 'visa-faq')->first();
        $tm30Cat    = Category::where('type', 'faq')->where('slug', 'tm30-faq')->first();
        $reportCat  = Category::where('type', 'faq')->where('slug', 'reporting-faq')->first();
        $extCat     = Category::where('type', 'faq')->where('slug', 'extension-faq')->first();
        $genCat     = Category::where('type', 'faq')->where('slug', 'general-faq')->first();

        $faqs = [
            // Visa FAQs
            [
                'category_id' => $visaCat?->id,
                'question_th' => 'ฉันต้องมีวีซ่าเพื่อเข้าประเทศไทยหรือไม่?',
                'question_en' => 'Do I need a visa to enter Thailand?',
                'answer_th'   => '<p>ขึ้นอยู่กับสัญชาติของคุณ พลเมืองจาก 65 ประเทศสามารถเข้าไทยได้โดยไม่ต้องมีวีซ่า (ยกเว้นวีซ่า) เป็นระยะเวลา 30 วัน กรุณาตรวจสอบรายชื่อประเทศที่ได้รับการยกเว้นวีซ่าที่เว็บไซต์กรมการกงสุล</p>',
                'answer_en'   => '<p>It depends on your nationality. Citizens of 65 countries can enter Thailand visa-free for 30 days. Please check the list of visa-exempt countries on the Ministry of Foreign Affairs website.</p>',
                'sort_order'  => 1,
            ],
            [
                'category_id' => $visaCat?->id,
                'question_th' => 'วีซ่าท่องเที่ยวมีอายุนานแค่ไหน?',
                'question_en' => 'How long is a tourist visa valid?',
                'answer_th'   => '<p>วีซ่าท่องเที่ยว (TR) อนุญาตให้อยู่ได้ 60 วัน และสามารถต่ออายุได้อีก 30 วัน ที่สำนักงาน ตม. ค่าธรรมเนียมการต่อ 1,900 บาท</p>',
                'answer_en'   => '<p>A Tourist Visa (TR) allows a stay of 60 days and can be extended once for 30 days at an immigration office. The extension fee is THB 1,900.</p>',
                'sort_order'  => 2,
            ],

            // TM30 FAQs
            [
                'category_id' => $tm30Cat?->id,
                'question_th' => 'TM30 คืออะไร?',
                'question_en' => 'What is TM30?',
                'answer_th'   => '<p>TM30 คือแบบฟอร์มที่เจ้าของที่พัก (โรงแรม บ้านเช่า นายจ้าง) ต้องยื่นต่อ ตม. เพื่อแจ้งว่ามีคนต่างด้าวมาพักอาศัย ต้องยื่นภายใน 24 ชั่วโมงหลังจากคนต่างด้าวมาถึง</p>',
                'answer_en'   => '<p>TM30 is a form that accommodation providers (hotels, landlords, employers) must submit to immigration to notify that a foreigner is residing at their address. It must be filed within 24 hours of the foreigner\'s arrival.</p>',
                'sort_order'  => 1,
            ],
            [
                'category_id' => $tm30Cat?->id,
                'question_th' => 'ถ้าไม่ยื่น TM30 จะมีโทษอะไร?',
                'question_en' => 'What is the penalty for not filing TM30?',
                'answer_th'   => '<p>มีโทษปรับสูงสุด 2,000 บาท สำหรับเจ้าของที่พัก และคนต่างด้าวอาจถูกปฏิเสธการต่ออายุวีซ่าหรือถูกลงโทษเพิ่มเติม</p>',
                'answer_en'   => '<p>The accommodation provider may be fined up to THB 2,000. The foreign national may also face difficulties renewing their visa or may face additional penalties.</p>',
                'sort_order'  => 2,
            ],

            // 90-Day Report FAQs
            [
                'category_id' => $reportCat?->id,
                'question_th' => 'ต้องรายงานตัว 90 วันที่ไหน?',
                'question_en' => 'Where do I report for the 90-day check-in?',
                'answer_th'   => '<p>รายงานตัวได้ที่สำนักงาน ตม. ในพื้นที่ที่คุณพักอาศัย หรือออนไลน์ผ่านเว็บไซต์ immigration.go.th หรือส่งทางไปรษณีย์</p>',
                'answer_en'   => '<p>You can report at your local immigration office, online at immigration.go.th, or by registered mail.</p>',
                'sort_order'  => 1,
            ],

            // Extension FAQs
            [
                'category_id' => $extCat?->id,
                'question_th' => 'ค่าธรรมเนียมการต่ออายุวีซ่าเท่าไร?',
                'question_en' => 'How much does a visa extension cost?',
                'answer_th'   => '<p>ค่าธรรมเนียมการต่ออายุวีซ่าคือ <strong>1,900 บาท</strong> ต่อครั้ง ไม่มีการคืนเงินหากถูกปฏิเสธ</p>',
                'answer_en'   => '<p>The visa extension fee is <strong>THB 1,900</strong> per application. Fees are non-refundable if the application is rejected.</p>',
                'sort_order'  => 1,
            ],

            // General FAQs
            [
                'category_id' => $genCat?->id,
                'question_th' => 'สายด่วน ตม. คือเบอร์อะไร?',
                'question_en' => 'What is the immigration hotline number?',
                'answer_th'   => '<p>สายด่วนตรวจคนเข้าเมืองคือ <strong>1178</strong> ให้บริการตลอด 24 ชั่วโมง</p>',
                'answer_en'   => '<p>The immigration hotline is <strong>1178</strong>, available 24 hours a day.</p>',
                'sort_order'  => 1,
            ],
            [
                'category_id' => $genCat?->id,
                'question_th' => 'เวลาทำการสำนักงาน ตม. คือเมื่อไร?',
                'question_en' => 'What are the immigration office hours?',
                'answer_th'   => '<p>สำนักงาน ตม. เปิดทำการ <strong>วันจันทร์–ศุกร์ เวลา 08:30–16:30 น.</strong> ปิดวันเสาร์–อาทิตย์และวันหยุดนักขัตฤกษ์</p>',
                'answer_en'   => '<p>Immigration offices are open <strong>Monday–Friday, 08:30–16:30</strong>. Closed on weekends and public holidays.</p>',
                'sort_order'  => 2,
            ],
        ];

        foreach ($faqs as $data) {
            Faq::create(array_merge($data, ['is_public' => true, 'is_active' => true]));
        }

        $this->command->info('✅ FAQs seeded: ' . count($faqs));
    }
}
