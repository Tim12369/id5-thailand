<?php

namespace Database\Seeders;

use App\Models\GuideTopic;
use App\Models\PublicForm;
use App\Models\Station;
use Illuminate\Database\Seeder;

class GuideTopicSeeder extends Seeder
{
    public function run(): void
    {
        $tm30Form = PublicForm::where('code', 'TM30')->first();
        $tm47Form = PublicForm::where('code', 'TM47')->first();
        $cmStation = Station::where('slug', 'cm01-chiang-mai-immigration-office')->first();

        $this->seedTopic('visa', 'ti-passport', 'blue', 1, null, false,
            'tourist visa, non-immigrant visa, student visa, retirement visa, e-visa, visa application, วีซ่า, ขอวีซ่า',
            documents: [
                ['name_th' => 'หนังสือเดินทาง (อายุเหลือไม่น้อยกว่า 18 เดือน)', 'name_en' => 'Passport (valid 18+ months)', 'required' => true],
                ['name_th' => 'รูปถ่าย 4x6 ซม. จำนวน 2 รูป', 'name_en' => '2 passport photos (4x6 cm)', 'required' => true],
                ['name_th' => 'หลักฐานการจองที่พัก', 'name_en' => 'Hotel booking confirmation', 'required' => true],
                ['name_th' => 'ตั๋วเครื่องบินขาออก', 'name_en' => 'Return/onward flight ticket', 'required' => true],
                ['name_th' => 'หลักฐานการเงิน (20,000 บาท/คน)', 'name_en' => 'Proof of funds (THB 20,000/person)', 'required' => true],
            ],
            steps: [
                ['title_th' => 'รวบรวมเอกสารทั้งหมด', 'title_en' => 'Gather all required documents'],
                ['title_th' => 'กรอกแบบฟอร์มวีซ่า', 'title_en' => 'Complete the visa application form'],
                ['title_th' => 'ยื่นที่สถานทูตหรือออนไลน์', 'title_en' => 'Submit at the Embassy or online'],
                ['title_th' => 'ชำระค่าธรรมเนียม', 'title_en' => 'Pay the visa fee'],
                ['title_th' => 'รอผลและรับวีซ่าในหนังสือเดินทาง', 'title_en' => 'Wait for processing and collect your visa'],
            ],
            locations: [
                ['type' => 'embassy', 'label_th' => 'สถานเอกอัครราชทูต/สถานกงสุลไทย', 'label_en' => 'Thai Embassy / Consulate', 'url' => null],
                ['type' => 'online', 'label_th' => 'ระบบ e-Visa', 'label_en' => 'e-Visa Online System', 'url' => 'https://www.thaievisa.go.th'],
                ['type' => 'office', 'label_th' => 'สำนักงานตรวจคนเข้าเมือง (สำหรับต่อวีซ่า)', 'label_en' => 'Immigration Bureau (for extensions)', 'station_id' => $cmStation?->id],
            ],
        );

        $this->seedTopic('tm30', 'ti-file-text', 'teal', 2, 1, true,
            'tm30, accommodation report, foreigner notification, แจ้งที่พัก, ทีเอ็ม30',
            documents: [
                ['name_th' => 'สำเนาหนังสือเดินทางของผู้เข้าพัก', 'name_en' => 'Copy of guest passport', 'required' => true],
                ['name_th' => 'แบบฟอร์ม TM30', 'name_en' => 'TM30 form', 'required' => true, 'form_id' => $tm30Form?->id],
                ['name_th' => 'หลักฐานที่อยู่ที่พัก', 'name_en' => 'Proof of accommodation address', 'required' => false],
            ],
            steps: [
                ['title_th' => 'ตรวจสอบว่าคุณมีหน้าที่ต้องแจ้งหรือไม่', 'title_en' => 'Confirm you are obligated to file'],
                ['title_th' => 'เตรียมข้อมูลที่ต้องใช้', 'title_en' => 'Gather guest information'],
                ['title_th' => 'เลือกช่องทาง: ออนไลน์หรือที่สำนักงาน', 'title_en' => 'Choose online or in-person filing'],
                ['title_th' => 'ส่งแบบฟอร์มและรับใบรับแจ้ง', 'title_en' => 'Submit the form and receive your receipt'],
                ['title_th' => 'เก็บใบรับแจ้งไว้เป็นหลักฐาน', 'title_en' => 'Keep the receipt as proof'],
            ],
            locations: [
                ['type' => 'online', 'label_th' => 'ระบบ TM30 ออนไลน์', 'label_en' => 'TM30 Online System', 'url' => 'https://tm30.immigration.go.th'],
                ['type' => 'office', 'label_th' => 'สำนักงานตรวจคนเข้าเมืองในพื้นที่', 'label_en' => 'Local Immigration Office', 'station_id' => $cmStation?->id],
            ],
        );

        $this->seedTopic('90day', 'ti-calendar-event', 'indigo', 3, 90, false,
            '90 day report, 90-day, tm47, รายงานตัว 90 วัน',
            documents: [
                ['name_th' => 'หนังสือเดินทางตัวจริง', 'name_en' => 'Original passport', 'required' => true],
                ['name_th' => 'แบบฟอร์ม TM47', 'name_en' => 'TM47 form', 'required' => true, 'form_id' => $tm47Form?->id],
                ['name_th' => 'ใบรับการรายงานตัวครั้งก่อน', 'name_en' => 'Previous acknowledgement slip', 'required' => false],
                ['name_th' => 'หลักฐานที่อยู่อาศัย', 'name_en' => 'Proof of residence', 'required' => false],
            ],
            steps: [
                ['title_th' => 'รู้วันครบกำหนดของคุณ', 'title_en' => 'Know your due date'],
                ['title_th' => 'เตรียมเอกสารให้พร้อม', 'title_en' => 'Prepare your documents'],
                ['title_th' => 'เลือกวิธีรายงาน: ออนไลน์/สำนักงาน/ไปรษณีย์', 'title_en' => 'Choose your reporting method'],
                ['title_th' => 'ยื่นแบบฟอร์มและรับใบรับ', 'title_en' => 'Submit and receive your receipt'],
                ['title_th' => 'จดวันครบกำหนดครั้งต่อไป', 'title_en' => 'Note your next due date'],
            ],
            locations: [
                ['type' => 'online', 'label_th' => 'ระบบ TM47 ออนไลน์', 'label_en' => 'TM47 Online System', 'url' => 'https://tm47.immigration.go.th'],
                ['type' => 'office', 'label_th' => 'สำนักงานตรวจคนเข้าเมือง', 'label_en' => 'Immigration Bureau', 'station_id' => $cmStation?->id],
                ['type' => 'other', 'label_th' => 'ส่งทางไปรษณีย์ลงทะเบียน', 'label_en' => 'By registered mail', 'url' => null],
            ],
        );

        $this->seedTopic('work_permit', 'ti-briefcase', 'purple', 4, null, false,
            'work permit, labor permit, ใบอนุญาตทำงาน, work visa',
            documents: [
                ['name_th' => 'หนังสือเดินทางและวีซ่าทำงาน (Non-B)', 'name_en' => 'Passport and Non-B visa', 'required' => true],
                ['name_th' => 'แบบคำขอใบอนุญาตทำงาน', 'name_en' => 'Work permit application form', 'required' => true],
                ['name_th' => 'เอกสารบริษัทผู้ว่าจ้าง (หนังสือรับรอง, งบการเงิน)', 'name_en' => 'Employer company documents (registration, financials)', 'required' => true],
                ['name_th' => 'วุฒิการศึกษา/ใบรับรองประสบการณ์', 'name_en' => 'Education certificate / experience letter', 'required' => false],
            ],
            steps: [
                ['title_th' => 'นายจ้างยื่นขอโควต้าจ้างแรงงานต่างชาติ', 'title_en' => 'Employer applies for foreign worker quota'],
                ['title_th' => 'เตรียมเอกสารของพนักงาน', 'title_en' => 'Gather employee documents'],
                ['title_th' => 'ยื่นคำขอที่กรมการจัดหางาน', 'title_en' => 'Submit application to Department of Employment'],
                ['title_th' => 'ชำระค่าธรรมเนียม', 'title_en' => 'Pay the application fee'],
                ['title_th' => 'รับสมุดใบอนุญาตทำงาน', 'title_en' => 'Receive the work permit book'],
            ],
            locations: [
                ['type' => 'office', 'label_th' => 'กรมการจัดหางาน / One Stop Service Center', 'label_en' => 'Department of Employment / One Stop Service Center', 'station_id' => null],
                ['type' => 'online', 'label_th' => 'ระบบออนไลน์กรมการจัดหางาน', 'label_en' => 'Department of Employment Online System', 'url' => 'https://www.doe.go.th'],
            ],
        );

        $this->seedTopic('investor_visa', 'ti-chart-line', 'amber', 5, null, false,
            'investor visa, BOI smart visa, LTR visa, long-term resident, business visa, วีซ่านักลงทุน, วีซ่า BOI',
            documents: [
                ['name_th' => 'หนังสือเดินทางและแผนธุรกิจ/ข้อเสนอการลงทุน', 'name_en' => 'Passport and business plan / investment proposal', 'required' => true],
                ['name_th' => 'หลักฐานแหล่งที่มาของเงินทุน (รายการเดินบัญชี)', 'name_en' => 'Proof of source of investment funds (bank statements)', 'required' => true],
                ['name_th' => 'เอกสารจดทะเบียนบริษัทไทย หรือหนังสือรับรองการส่งเสริมจาก BOI', 'name_en' => 'Thai company registration or BOI promotion certificate', 'required' => true],
                ['name_th' => 'หลักฐานการลงทุนขั้นต่ำตามประเภทวีซ่า (เช่น 20 ล้านบาทสำหรับ SMART, 500,000 ดอลลาร์สหรัฐสำหรับ LTR)', 'name_en' => 'Evidence of minimum investment per visa type (e.g. THB 20M for SMART, USD 500,000 for LTR)', 'required' => true],
            ],
            steps: [
                ['title_th' => 'เลือกแนวทางการลงทุน (BOI, จัดตั้งบริษัทไทย, หรือ LTR)', 'title_en' => 'Choose an investment route (BOI, Thai company, or LTR)'],
                ['title_th' => 'เตรียมหลักฐานแหล่งที่มาของเงินทุนและเอกสารธุรกิจ', 'title_en' => 'Prepare proof of funds and business documentation'],
                ['title_th' => 'ยื่นคำขอที่ BOI หรือสำนักงานตรวจคนเข้าเมือง', 'title_en' => 'Apply at BOI or the Immigration Bureau'],
                ['title_th' => 'เข้าสัมภาษณ์/ตรวจสอบเอกสาร', 'title_en' => 'Attend interview / document verification'],
                ['title_th' => 'รับการอนุมัติและวีซ่า', 'title_en' => 'Receive approval and the visa'],
            ],
            locations: [
                ['type' => 'office', 'label_th' => 'ศูนย์ BOI One Start One Stop Investment Center', 'label_en' => 'BOI One Start One Stop Investment Center', 'station_id' => null],
                ['type' => 'office', 'label_th' => 'กองบริหารแรงงานต่างด้าว (วีซ่า LTR)', 'label_en' => 'Long-Term Resident Visa Unit, Immigration Bureau', 'station_id' => $cmStation?->id],
                ['type' => 'embassy', 'label_th' => 'สถานเอกอัครราชทูตไทย (สำหรับวีซ่า Non-B เบื้องต้น)', 'label_en' => 'Thai Embassy (for initial Non-B visa)', 'url' => null],
            ],
        );

        $this->seedTopic('marriage_visa', 'ti-heart', 'pink', 6, 365, false,
            'marriage visa, non-o visa, spouse visa, married to thai, วีซ่าคู่สมรส, วีซ่าแต่งงาน',
            documents: [
                ['name_th' => 'ทะเบียนสมรสไทย', 'name_en' => 'Thai marriage certificate', 'required' => true],
                ['name_th' => 'บัตรประชาชนและทะเบียนบ้านของคนไทย', 'name_en' => 'Thai citizen\'s ID card and house registration', 'required' => true],
                ['name_th' => 'หนังสือเดินทางของคู่สมรสชาวต่างชาติ', 'name_en' => 'Foreign spouse\'s passport', 'required' => true],
                ['name_th' => 'หลักฐานการเงิน: เงินฝาก 400,000 บาท หรือรายได้ 40,000 บาท/เดือน', 'name_en' => 'Financial evidence: THB 400,000 deposit or THB 40,000/month income', 'required' => true],
                ['name_th' => 'รูปถ่ายคู่และหลักฐานความสัมพันธ์', 'name_en' => 'Photos of the couple and evidence of relationship', 'required' => false],
            ],
            steps: [
                ['title_th' => 'จดทะเบียนสมรสที่สำนักงานเขต/อำเภอ', 'title_en' => 'Register the marriage at the District/Amphur Office'],
                ['title_th' => 'เตรียมหลักฐานการเงิน', 'title_en' => 'Prepare financial evidence'],
                ['title_th' => 'ยื่นขอวีซ่า Non-Immigrant O', 'title_en' => 'Apply for the Non-Immigrant O visa'],
                ['title_th' => 'ยื่นคำขอขยายระยะเวลาพำนัก 1 ปี', 'title_en' => 'Apply for the 1-year extension of stay'],
                ['title_th' => 'แจ้ง TM30 และติดตามการรายงานตัว 90 วัน', 'title_en' => 'File TM30 and track 90-day reporting'],
            ],
            locations: [
                ['type' => 'office', 'label_th' => 'สำนักงานเขต/อำเภอ (จดทะเบียนสมรส)', 'label_en' => 'District / Amphur Office (marriage registration)', 'station_id' => null],
                ['type' => 'embassy', 'label_th' => 'สถานเอกอัครราชทูตไทย (วีซ่า Non-O เบื้องต้น)', 'label_en' => 'Thai Embassy (initial Non-O visa)', 'url' => null],
                ['type' => 'office', 'label_th' => 'สำนักงานตรวจคนเข้าเมือง (ต่ออายุการพำนัก)', 'label_en' => 'Immigration Bureau (extension of stay)', 'station_id' => $cmStation?->id],
            ],
        );
    }

    private function seedTopic(
        string $topic,
        string $icon,
        string $color,
        int $sortOrder,
        ?int $deadlineDays,
        bool $isUrgent,
        string $keywords,
        array $documents,
        array $steps,
        array $locations,
    ): void {
        $guideTopic = GuideTopic::updateOrCreate(
            ['topic' => $topic],
            [
                'icon' => $icon,
                'color' => $color,
                'search_keywords' => $keywords,
                'deadline_days' => $deadlineDays,
                'is_urgent' => $isUrgent,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]
        );

        foreach ($documents as $i => $doc) {
            $guideTopic->documents()->updateOrCreate(
                ['name_en' => $doc['name_en']],
                [
                    'name_th' => $doc['name_th'],
                    'is_required' => $doc['required'],
                    'form_id' => $doc['form_id'] ?? null,
                    'sort_order' => $i + 1,
                ]
            );
        }

        foreach ($steps as $i => $step) {
            $guideTopic->steps()->updateOrCreate(
                ['step_number' => $i + 1],
                [
                    'title_th' => $step['title_th'],
                    'title_en' => $step['title_en'],
                    'sort_order' => $i + 1,
                ]
            );
        }

        foreach ($locations as $i => $loc) {
            $guideTopic->locations()->updateOrCreate(
                ['label_en' => $loc['label_en']],
                [
                    'type' => $loc['type'],
                    'label_th' => $loc['label_th'],
                    'url' => $loc['url'] ?? null,
                    'station_id' => $loc['station_id'] ?? null,
                    'sort_order' => $i + 1,
                ]
            );
        }
    }
}
