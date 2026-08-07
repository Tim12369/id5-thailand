<?php

namespace Database\Seeders;

use App\Models\SmartGuide;
use Illuminate\Database\Seeder;

class SmartGuideSeeder extends Seeder
{
    public function run(): void
    {
        $entries = [
            // Tourist + Visa
            ['persona' => 'tourist', 'topic' => 'visa', 'dimension' => 'who',
             'content_th' => '<p>นักท่องเที่ยวต่างชาติที่ต้องการเดินทางเข้าประเทศไทยและประเทศไทยไม่ได้ยกเว้นวีซ่าให้ หรือต้องการอยู่นานกว่าระยะเวลายกเว้นวีซ่า</p>',
             'content_en' => '<p>Foreign tourists who need to enter Thailand and are not eligible for a visa exemption, or who wish to stay longer than the exemption period.</p>'],

            ['persona' => 'tourist', 'topic' => 'visa', 'dimension' => 'what',
             'content_th' => '<p><strong>เอกสารที่ต้องใช้:</strong></p><ul><li>หนังสือเดินทางที่มีอายุไม่น้อยกว่า 18 เดือน</li><li>รูปถ่าย 4x6 cm จำนวน 2 รูป</li><li>แบบฟอร์มวีซ่า (กรอกครบถ้วน)</li><li>หลักฐานการจองที่พัก</li><li>ตั๋วเครื่องบินขาออก</li><li>หลักฐานการเงิน (20,000 บาท/ครอบครัว)</li></ul>',
             'content_en' => '<p><strong>Required documents:</strong></p><ul><li>Passport valid for at least 18 months</li><li>2 passport photos (4x6 cm)</li><li>Completed visa application form</li><li>Hotel booking confirmation</li><li>Return/onward flight ticket</li><li>Proof of funds (THB 20,000 per person / 40,000 per family)</li></ul>'],

            ['persona' => 'tourist', 'topic' => 'visa', 'dimension' => 'why',
             'content_th' => '<p>ประเทศไทยกำหนดให้ชาวต่างชาติบางสัญชาติต้องมีวีซ่าเพื่อเข้าประเทศ เพื่อควบคุมจำนวนผู้เดินทางและรักษาความปลอดภัยของประเทศ</p>',
             'content_en' => '<p>Thailand requires visas from certain nationalities to control immigration and ensure national security. A visa confirms that you have permission to enter the country.</p>'],

            ['persona' => 'tourist', 'topic' => 'visa', 'dimension' => 'where',
             'content_th' => '<p><strong>ยื่นวีซ่าได้ที่:</strong></p><ul><li>สถานเอกอัครราชทูตหรือสถานกงสุลไทยในประเทศของคุณ</li><li>ออนไลน์ผ่านระบบ e-Visa (บางสัญชาติ)</li></ul>',
             'content_en' => '<p><strong>Where to apply:</strong></p><ul><li>Thai Embassy or Consulate in your country</li><li>Online via the e-Visa system (select nationalities)</li></ul>'],

            ['persona' => 'tourist', 'topic' => 'visa', 'dimension' => 'when',
             'content_th' => '<p>ยื่นคำขอวีซ่าล่วงหน้าก่อนเดินทางอย่างน้อย <strong>2-4 สัปดาห์</strong> วีซ่าท่องเที่ยวมีอายุ 60 วัน (ต่อได้อีก 30 วัน)</p>',
             'content_en' => '<p>Apply at least <strong>2–4 weeks</strong> before your travel date. A tourist visa is valid for 60 days and can be extended once for 30 days.</p>'],

            ['persona' => 'tourist', 'topic' => 'visa', 'dimension' => 'how',
             'content_th' => '<ol><li>รวบรวมเอกสารทั้งหมด</li><li>กรอกแบบฟอร์มวีซ่า</li><li>ยื่นที่สถานทูตหรือออนไลน์</li><li>ชำระค่าธรรมเนียม (1,000–2,000 บาทแล้วแต่ประเภท)</li><li>รอผล 5-7 วันทำการ</li><li>รับวีซ่าในหนังสือเดินทาง</li></ol>',
             'content_en' => '<ol><li>Gather all required documents</li><li>Complete the visa application form</li><li>Submit at the Embassy or online</li><li>Pay the visa fee (THB 1,000–2,000 depending on type)</li><li>Wait 5–7 business days for processing</li><li>Collect passport with visa stamp</li></ol>'],

            // Hotel + TM30
            ['persona' => 'hotel', 'topic' => 'tm30', 'dimension' => 'who',
             'content_th' => '<p>เจ้าของหรือผู้จัดการโรงแรม รีสอร์ท บ้านพัก อพาร์ตเมนต์ หรือที่พักทุกประเภทที่รับคนต่างด้าวเข้าพัก</p>',
             'content_en' => '<p>Owners or managers of hotels, resorts, apartments, guesthouses, or any accommodation that accepts foreign guests.</p>'],

            ['persona' => 'hotel', 'topic' => 'tm30', 'dimension' => 'what',
             'content_th' => '<p><strong>ข้อมูลที่ต้องแจ้ง:</strong></p><ul><li>ชื่อ-นามสกุลคนต่างด้าว</li><li>เลขที่หนังสือเดินทาง</li><li>สัญชาติ</li><li>วันที่เช็คอิน</li><li>ที่อยู่ที่พัก</li></ul>',
             'content_en' => '<p><strong>Information required:</strong></p><ul><li>Foreigner\'s full name</li><li>Passport number</li><li>Nationality</li><li>Check-in date</li><li>Accommodation address</li></ul>'],

            ['persona' => 'hotel', 'topic' => 'tm30', 'dimension' => 'why',
             'content_th' => '<p>ตามมาตรา 38 แห่งพระราชบัญญัติคนเข้าเมือง พ.ศ. 2522 เจ้าบ้านหรือผู้ครอบครองสถานที่พักที่รับคนต่างด้าวเข้าพักอาศัย มีหน้าที่ตามกฎหมายต้องแจ้งต่อพนักงานเจ้าหน้าที่ เพื่อให้ทางราชการสามารถตรวจสอบและติดตามที่อยู่ของคนต่างด้าวในประเทศได้ ซึ่งเป็นส่วนหนึ่งของการรักษาความมั่นคงและความปลอดภัยของประเทศ</p>',
             'content_en' => '<p>Under Section 38 of the Immigration Act B.E. 2522 (1979), any house owner or occupant of premises that accommodates a foreigner has a legal duty to notify the authorities. This allows the government to verify and track the whereabouts of foreigners in the country, supporting national security.</p>'],

            ['persona' => 'hotel', 'topic' => 'tm30', 'dimension' => 'where',
             'content_th' => '<p><strong>แจ้งได้ที่:</strong></p><ul><li>ระบบออนไลน์ที่ tm30.immigration.go.th (แนะนำ ทำได้ตลอด 24 ชั่วโมง)</li><li>สำนักงานตรวจคนเข้าเมืองในพื้นที่ที่ตั้งของที่พัก</li></ul>',
             'content_en' => '<p><strong>Where to report:</strong></p><ul><li>Online at tm30.immigration.go.th (recommended, available 24/7)</li><li>The local Immigration Bureau office covering the accommodation\'s location</li></ul>'],

            ['persona' => 'hotel', 'topic' => 'tm30', 'dimension' => 'when',
             'content_th' => '<p>ต้องแจ้งภายใน <strong>24 ชั่วโมง</strong> หลังจากคนต่างด้าวเข้าพัก หากไม่แจ้งมีโทษปรับสูงสุด 2,000 บาท</p>',
             'content_en' => '<p>Must be filed within <strong>24 hours</strong> of the foreigner\'s check-in. Failure to file carries a fine of up to THB 2,000.</p>'],

            ['persona' => 'hotel', 'topic' => 'tm30', 'dimension' => 'how',
             'content_th' => '<ol><li>เตรียมสำเนาหนังสือเดินทางของแขก</li><li>กรอกแบบฟอร์ม TM30</li><li>ยื่นที่ ตม. ในพื้นที่หรือผ่านระบบออนไลน์</li><li>เก็บหลักฐานการแจ้งไว้อย่างน้อย 3 ปี</li></ol>',
             'content_en' => '<ol><li>Obtain a copy of the guest\'s passport</li><li>Complete TM30 form</li><li>Submit at local immigration office or online</li><li>Keep the receipt for at least 3 years</li></ol>'],

            // Student + 90day
            ['persona' => 'student', 'topic' => '90day', 'dimension' => 'who',
             'content_th' => '<p>ชาวต่างชาติที่พำนักในประเทศไทยด้วยวีซ่าระยะยาว (นักเรียน แรงงาน ผู้เกษียณ ฯลฯ) และอยู่เกิน 90 วันต่อเนื่อง</p>',
             'content_en' => '<p>Foreign nationals residing in Thailand on a long-stay visa (student, worker, retiree, etc.) who have been in the country for more than 90 consecutive days.</p>'],

            ['persona' => 'student', 'topic' => '90day', 'dimension' => 'what',
             'content_th' => '<p><strong>เอกสารที่ต้องใช้:</strong></p><ul><li>หนังสือเดินทางตัวจริง พร้อมวีซ่าและตราประทับเข้าเมืองล่าสุด</li><li>แบบฟอร์ม TM47 กรอกครบถ้วน</li><li>ใบรับการรายงานตัวครั้งก่อน (ถ้ามี)</li><li>หลักฐานที่อยู่อาศัย เช่น สัญญาเช่า หรือใบรับ TM30</li></ul>',
             'content_en' => '<p><strong>Required documents:</strong></p><ul><li>Original passport with current visa and latest entry stamp</li><li>Completed TM47 form</li><li>Previous acknowledgement slip (if available)</li><li>Proof of residence (lease agreement or TM30 receipt)</li></ul>'],

            ['persona' => 'student', 'topic' => '90day', 'dimension' => 'why',
             'content_th' => '<p>ตามมาตรา 37(5) แห่งพระราชบัญญัติคนเข้าเมือง พ.ศ. 2522 ชาวต่างชาติที่พำนักในประเทศไทยต่อเนื่องเกิน 90 วัน ต้องแจ้งที่อยู่ปัจจุบันต่อเจ้าพนักงานตรวจคนเข้าเมือง เพื่อให้ทางราชการสามารถติดตามที่อยู่ของผู้พำนักระยะยาวได้ หากไม่แจ้งจะมีโทษปรับและอาจกระทบต่อการต่อวีซ่าในอนาคต</p>',
             'content_en' => '<p>Under Section 37(5) of the Immigration Act B.E. 2522 (1979), foreigners residing in Thailand continuously for more than 90 days must report their current address to immigration officers. This allows the government to track the whereabouts of long-stay residents. Failure to report carries a fine and may affect future visa extensions.</p>'],

            ['persona' => 'student', 'topic' => '90day', 'dimension' => 'where',
             'content_th' => '<p><strong>รายงานตัวได้ที่:</strong></p><ul><li>ระบบออนไลน์ที่ tm47.immigration.go.th</li><li>สำนักงานตรวจคนเข้าเมืองในพื้นที่ที่พำนัก</li><li>ทางไปรษณีย์ลงทะเบียน (ส่งล่วงหน้าอย่างน้อย 7 วัน)</li></ul>',
             'content_en' => '<p><strong>Where to report:</strong></p><ul><li>Online at tm47.immigration.go.th</li><li>Local Immigration Bureau office where you reside</li><li>By registered mail (sent at least 7 days in advance)</li></ul>'],

            ['persona' => 'student', 'topic' => '90day', 'dimension' => 'when',
             'content_th' => '<p>รายงานตัวทุก <strong>90 วัน</strong> นับจากวันที่เข้าประเทศหรือวันที่รายงานตัวครั้งล่าสุด ควรรายงานก่อนครบกำหนด 7 วัน</p>',
             'content_en' => '<p>Report every <strong>90 days</strong> from your last entry date or last report date. Recommended to report up to 7 days before the deadline.</p>'],

            ['persona' => 'student', 'topic' => '90day', 'dimension' => 'how',
             'content_th' => '<ol><li>เตรียมหนังสือเดินทาง + สำเนา</li><li>กรอกแบบฟอร์ม TM47</li><li>ยื่นที่ ตม. ในพื้นที่ หรือออนไลน์ที่ immigration.go.th หรือส่งทางไปรษณีย์</li><li>รับหลักฐานและเก็บไว้</li></ol>',
             'content_en' => '<ol><li>Prepare passport + copy</li><li>Complete TM47 form</li><li>Submit at local immigration office, online at immigration.go.th, or by post</li><li>Receive and keep the receipt</li></ol>'],

            // Worker + Work Permit (the foreign worker's own process)
            ['persona' => 'worker', 'topic' => 'work_permit', 'dimension' => 'who',
             'content_th' => '<p>ชาวต่างชาติที่ต้องการทำงานในประเทศไทย ไม่ว่าจะเป็นลูกจ้างของบริษัทไทย ผู้บริหาร ผู้เชี่ยวชาญ หรือพนักงานที่ถูกส่งมาประจำการ ต้องมีใบอนุญาตทำงานก่อนเริ่มทำงานทุกกรณี</p>',
             'content_en' => '<p>Foreign nationals who wish to work in Thailand — whether as an employee of a Thai company, an executive, a specialist, or someone seconded by a foreign parent company — must hold a work permit before starting work in every case.</p>'],

            ['persona' => 'worker', 'topic' => 'work_permit', 'dimension' => 'what',
             'content_th' => '<p><strong>เอกสารที่ต้องใช้:</strong></p><ul><li>หนังสือเดินทางพร้อมวีซ่า Non-Immigrant B (ธุรกิจ)</li><li>แบบคำขอใบอนุญาตทำงาน (ตท.3)</li><li>วุฒิการศึกษา/ใบรับรองความเชี่ยวชาญ (ตามตำแหน่งงาน)</li><li>ใบรับรองแพทย์</li><li>เอกสารบริษัทผู้ว่าจ้าง (หนังสือรับรอง, งบการเงิน, ภ.พ.20)</li><li>รูปถ่าย</li></ul>',
             'content_en' => '<p><strong>Required documents:</strong></p><ul><li>Passport with a Non-Immigrant B (Business) visa</li><li>Work permit application form (Tor Tor 3)</li><li>Educational qualifications or professional certification relevant to the job</li><li>Medical certificate</li><li>Employer\'s company documents (affidavit, financial statements, VAT registration)</li><li>Photographs</li></ul>'],

            ['persona' => 'worker', 'topic' => 'work_permit', 'dimension' => 'why',
             'content_th' => '<p>ตามพระราชบัญญัติการบริหารจัดการการทำงานของคนต่างด้าว พ.ศ. 2560 ชาวต่างชาติที่ทำงานในประเทศไทยโดยไม่มีใบอนุญาตทำงาน ถือว่ามีความผิดตามกฎหมาย มีโทษปรับและอาจถูกเนรเทศ นายจ้างที่จ้างคนต่างด้าวโดยไม่มีใบอนุญาตก็มีความผิดเช่นกัน</p>',
             'content_en' => '<p>Under the Act on the Management of Foreign Workers\' Employment B.E. 2560 (2017), working in Thailand without a valid work permit is a legal offense carrying fines and possible deportation. Employers who hire a foreign worker without a permit are also liable.</p>'],

            ['persona' => 'worker', 'topic' => 'work_permit', 'dimension' => 'where',
             'content_th' => '<p><strong>ยื่นได้ที่:</strong></p><ul><li>กรมการจัดหางาน กระทรวงแรงงาน</li><li>ศูนย์บริการวีซ่าและใบอนุญาตทำงาน (One Start One Stop Investment Center) สำหรับบริษัทที่ได้รับการส่งเสริมจาก BOI</li><li>ระบบออนไลน์ที่ wp.doe.go.th</li></ul>',
             'content_en' => '<p><strong>Where to apply:</strong></p><ul><li>Department of Employment, Ministry of Labour</li><li>One Start One Stop Investment Center (OSOS) for BOI-promoted companies</li><li>Online via wp.doe.go.th</li></ul>'],

            ['persona' => 'worker', 'topic' => 'work_permit', 'dimension' => 'when',
             'content_th' => '<p>ต้องได้รับใบอนุญาตทำงาน<strong>ก่อนเริ่มทำงาน</strong>เสมอ และต้องยื่นต่ออายุก่อนใบอนุญาตหรือวีซ่าหมดอายุ (โดยทั่วไปมีอายุ 1 ปีตามวีซ่า)</p>',
             'content_en' => '<p>The work permit must be obtained <strong>before starting work</strong> in every case, and renewed before it or the visa expires (typically valid for 1 year, matching the visa).</p>'],

            ['persona' => 'worker', 'topic' => 'work_permit', 'dimension' => 'how',
             'content_th' => '<ol><li>ขอวีซ่า Non-Immigrant B จากสถานทูตไทยหรือในประเทศ</li><li>นายจ้างยื่นขอโควต้าและเอกสารสนับสนุน</li><li>ยื่นคำขอใบอนุญาตทำงานพร้อมเอกสารครบถ้วน</li><li>ชำระค่าธรรมเนียม (ขึ้นอยู่กับระยะเวลาที่ขอ)</li><li>รับสมุดใบอนุญาตทำงาน</li><li>พกใบอนุญาตทำงานตัวจริงติดตัวขณะทำงานเสมอ</li></ol>',
             'content_en' => '<ol><li>Obtain a Non-Immigrant B visa from a Thai embassy or in-country</li><li>Employer submits the quota request and supporting documents</li><li>Submit the work permit application with all required documents</li><li>Pay the applicable fee (depends on the validity period requested)</li><li>Receive the work permit book</li><li>Always carry the original work permit while working</li></ol>'],

            // Employer + Work Permit (the sponsoring company's obligations)
            ['persona' => 'employer', 'topic' => 'work_permit', 'dimension' => 'who',
             'content_th' => '<p>บริษัทไทยหรือธุรกิจที่ได้รับการส่งเสริมการลงทุน (BOI) ที่ต้องการจ้างพนักงานชาวต่างชาติ มีหน้าที่ตามกฎหมายในการสนับสนุนและยื่นขอใบอนุญาตทำงานให้พนักงาน</p>',
             'content_en' => '<p>Thai companies or BOI-promoted businesses that wish to hire foreign employees have a legal duty to sponsor and apply for the employee\'s work permit.</p>'],

            ['persona' => 'employer', 'topic' => 'work_permit', 'dimension' => 'what',
             'content_th' => '<p><strong>เอกสารที่บริษัทต้องเตรียม:</strong></p><ul><li>หนังสือรับรองบริษัท (อายุไม่เกิน 6 เดือน) และบัญชีรายชื่อผู้ถือหุ้น</li><li>งบการเงินและภาษีมูลค่าเพิ่ม (ภ.พ.20)</li><li>บัญชีรายชื่อพนักงานไทย (เพื่อตรวจสอบสัดส่วนการจ้างงาน)</li><li>หนังสือรับรองการจ้างงาน/สัญญาจ้าง</li><li>หนังสือมอบอำนาจ (กรณีผู้ยื่นไม่ใช่กรรมการ)</li></ul>',
             'content_en' => '<p><strong>Documents the company must prepare:</strong></p><ul><li>Company affidavit (not older than 6 months) and list of shareholders</li><li>Financial statements and VAT registration (Por.Por.20)</li><li>List of Thai employees (to verify the employment ratio)</li><li>Employment contract / letter confirming the position</li><li>Power of attorney (if the applicant is not a company director)</li></ul>'],

            ['persona' => 'employer', 'topic' => 'work_permit', 'dimension' => 'why',
             'content_th' => '<p>ตามพระราชกำหนดการบริหารจัดการการทำงานของคนต่างด้าว พ.ศ. 2560 นายจ้างที่จ้างคนต่างด้าวโดยไม่มีใบอนุญาตทำงาน หรือให้ทำงานนอกเหนือจากที่ได้รับอนุญาต มีความผิดและอาจถูกปรับสูงสุดถึง 100,000 บาทต่อคนต่างด้าวที่จ้างโดยผิดกฎหมาย จึงควรตรวจสอบและยื่นเอกสารให้ถูกต้องครบถ้วนก่อนให้พนักงานเริ่มงาน</p>',
             'content_en' => '<p>Under the Royal Ordinance on the Management of Foreign Workers\' Employment B.E. 2560 (2017), an employer who hires a foreign worker without a valid work permit, or allows work outside the scope permitted, commits an offense and can face a fine of up to THB 100,000 per illegally employed worker. Employers should verify and complete all documentation before the employee starts work.</p>'],

            ['persona' => 'employer', 'topic' => 'work_permit', 'dimension' => 'where',
             'content_th' => '<p><strong>ยื่นได้ที่:</strong></p><ul><li>กรมการจัดหางาน กระทรวงแรงงาน</li><li>ศูนย์ One Start One Stop Investment Center (OSOS) สำหรับธุรกิจที่ได้รับการส่งเสริมจาก BOI</li><li>ระบบออนไลน์ที่ wp.doe.go.th</li></ul>',
             'content_en' => '<p><strong>Where to apply:</strong></p><ul><li>Department of Employment, Ministry of Labour</li><li>One Start One Stop Investment Center (OSOS) for BOI-promoted businesses</li><li>Online via wp.doe.go.th</li></ul>'],

            ['persona' => 'employer', 'topic' => 'work_permit', 'dimension' => 'when',
             'content_th' => '<p>ต้องยื่นและได้รับใบอนุญาตทำงาน<strong>ก่อน</strong>ให้พนักงานต่างชาติเริ่มทำงานเสมอ และต้องยื่นต่ออายุก่อนใบอนุญาตหมดอายุ (โดยทั่วไปทุก 1 ปีตามวีซ่า)</p>',
             'content_en' => '<p>The work permit must be applied for and obtained <strong>before</strong> the foreign employee starts work, and renewed before it expires (typically annually, matching the visa).</p>'],

            ['persona' => 'employer', 'topic' => 'work_permit', 'dimension' => 'how',
             'content_th' => '<ol><li>ตรวจสอบคุณสมบัติบริษัท (เช่น ทุนจดทะเบียนและสัดส่วนพนักงานไทยต่อพนักงานต่างชาติ)</li><li>ยื่นขอโควต้าจ้างแรงงานต่างชาติที่กรมการจัดหางาน</li><li>สนับสนุนการขอวีซ่า Non-Immigrant B ให้พนักงาน</li><li>ยื่นคำขอใบอนุญาตทำงานร่วมกับเอกสารบริษัท</li><li>ชำระค่าธรรมเนียม</li><li>แจ้ง TM30 ที่อยู่พักอาศัยของพนักงานภายใน 24 ชั่วโมง และติดตามการรายงานตัว 90 วัน</li></ol>',
             'content_en' => '<ol><li>Verify the company meets eligibility requirements (e.g. registered capital and Thai-to-foreign employee ratio)</li><li>Apply for the foreign worker quota at the Department of Employment</li><li>Sponsor the employee\'s Non-Immigrant B visa application</li><li>Submit the work permit application together with company documents</li><li>Pay the applicable fees</li><li>File TM30 for the employee\'s residence within 24 hours, and track their 90-day reporting</li></ol>'],

            // Investor + Investor Visa
            ['persona' => 'investor', 'topic' => 'investor_visa', 'dimension' => 'who',
             'content_th' => '<p>ชาวต่างชาติที่ต้องการลงทุนในธุรกิจไทย จัดตั้งบริษัท หรือมีคุณสมบัติตามโครงการดึงดูดผู้มีศักยภาพสูงของประเทศไทย เช่น วีซ่า SMART หรือวีซ่าผู้พำนักระยะยาว (LTR)</p>',
             'content_en' => '<p>Foreign nationals who wish to invest in a Thai business, establish a company, or who qualify under Thailand\'s high-potential investor programs such as the SMART Visa or the Long-Term Resident (LTR) Visa.</p>'],

            ['persona' => 'investor', 'topic' => 'investor_visa', 'dimension' => 'what',
             'content_th' => '<p><strong>เอกสารที่ต้องใช้ (ขึ้นอยู่กับประเภทวีซ่า):</strong></p><ul><li>หนังสือเดินทางและแผนธุรกิจ/ข้อเสนอการลงทุน</li><li>หลักฐานแหล่งที่มาของเงินทุน (รายการเดินบัญชี)</li><li>เอกสารจดทะเบียนบริษัทไทย หรือหนังสือรับรองการส่งเสริมจาก BOI</li><li>สำหรับวีซ่า SMART (ประเภทนักลงทุน): หลักฐานการลงทุนขั้นต่ำ 20 ล้านบาทในกิจการที่ได้รับการส่งเสริม</li><li>สำหรับวีซ่า LTR (กลุ่มผู้มีความมั่งคั่งสูง): หลักฐานสินทรัพย์ส่วนตัวไม่น้อยกว่า 1 ล้านดอลลาร์สหรัฐ และการลงทุนในไทยไม่น้อยกว่า 500,000 ดอลลาร์สหรัฐ</li></ul>',
             'content_en' => '<p><strong>Required documents (vary by visa type):</strong></p><ul><li>Passport and business plan / investment proposal</li><li>Proof of source of investment funds (bank statements)</li><li>Thai company registration documents or a BOI promotion certificate</li><li>For the SMART Visa (Investor category): evidence of a minimum THB 20 million investment in a promoted business</li><li>For the LTR Visa (Wealthy Global Citizen category): evidence of personal assets of at least USD 1 million and an investment in Thailand of at least USD 500,000</li></ul>'],

            ['persona' => 'investor', 'topic' => 'investor_visa', 'dimension' => 'why',
             'content_th' => '<p>วีซ่านักลงทุนถูกออกแบบมาเพื่อดึงดูดเงินทุนจากต่างประเทศ ภายใต้พระราชบัญญัติการประกอบธุรกิจของคนต่างด้าว พ.ศ. 2542 ซึ่งจำกัดสัดส่วนการถือหุ้นของคนต่างด้าวในธุรกิจบางประเภท และเกณฑ์การส่งเสริมการลงทุนของสำนักงานคณะกรรมการส่งเสริมการลงทุน (BOI) รวมถึงโครงการวีซ่าผู้พำนักระยะยาว (LTR) ที่คณะรัฐมนตรีอนุมัติในปี 2565 เพื่อดึงดูดผู้มีศักยภาพสูงและนักลงทุนต่างชาติ</p>',
             'content_en' => '<p>Investor visas are designed to attract foreign capital under the Foreign Business Act B.E. 2542 (1999), which restricts the proportion of foreign ownership in certain business categories, and the investment promotion criteria of the Board of Investment (BOI). The Long-Term Resident (LTR) Visa program, approved by the Cabinet in 2022, was created to attract high-potential individuals and foreign investors.</p>'],

            ['persona' => 'investor', 'topic' => 'investor_visa', 'dimension' => 'where',
             'content_th' => '<p><strong>ยื่นได้ที่:</strong></p><ul><li>ศูนย์บริการวีซ่าและใบอนุญาตทำงาน BOI (One Start One Stop Investment Center)</li><li>กองบริหารแรงงานต่างด้าว สำหรับวีซ่า LTR (ฝ่ายตรวจลงตราและพำนักระยะยาว)</li><li>สถานเอกอัครราชทูตหรือสถานกงสุลไทยในต่างประเทศ สำหรับวีซ่า Non-Immigrant B (ธุรกิจ/ลงทุน) เบื้องต้น</li></ul>',
             'content_en' => '<p><strong>Where to apply:</strong></p><ul><li>BOI One Start One Stop Investment Center (OSOS), for the SMART Visa</li><li>Long-Term Resident Visa Unit at the Immigration Bureau, for the LTR Visa</li><li>Thai Embassy or Consulate abroad, for the initial Non-Immigrant B (Business/Investment) visa</li></ul>'],

            ['persona' => 'investor', 'topic' => 'investor_visa', 'dimension' => 'when',
             'content_th' => '<p>ควรยื่นขอวีซ่าก่อนเดินทางเข้าประเทศ และต้องคงไว้ซึ่งเงื่อนไขการลงทุนตลอดระยะเวลาที่ได้รับวีซ่า (วีซ่า LTR มีอายุ 10 ปี ส่วนวีซ่า SMART มีอายุสูงสุด 4 ปี ขึ้นอยู่กับประเภท)</p>',
             'content_en' => '<p>Apply before traveling to Thailand. The qualifying investment must be maintained for the duration of the visa (the LTR Visa is valid for 10 years; the SMART Visa is valid for up to 4 years depending on category).</p>'],

            ['persona' => 'investor', 'topic' => 'investor_visa', 'dimension' => 'how',
             'content_th' => '<ol><li>เลือกแนวทางการลงทุน (ส่งเสริมจาก BOI, จัดตั้งบริษัทไทย, หรือโครงการ LTR)</li><li>เตรียมหลักฐานแหล่งที่มาของเงินทุนและเอกสารธุรกิจ</li><li>ยื่นคำขอที่ BOI (วีซ่า SMART) หรือสำนักงานตรวจคนเข้าเมือง (วีซ่า LTR) หรือยื่นขอวีซ่า Non-B ที่สถานทูต</li><li>เข้าสัมภาษณ์/ตรวจสอบเอกสาร (หากจำเป็น)</li><li>รับการอนุมัติและวีซ่า</li><li>รักษาเงื่อนไขการลงทุนและรายงานต่อหน่วยงานที่เกี่ยวข้องตามที่กำหนด</li></ol>',
             'content_en' => '<ol><li>Choose an investment route (BOI promotion, Thai company registration, or the LTR program)</li><li>Prepare proof of funds and business/investment documentation</li><li>Apply at BOI (SMART Visa), the Immigration Bureau (LTR Visa), or a Thai embassy (initial Non-B visa)</li><li>Attend an interview or document verification if required</li><li>Receive approval and the visa</li><li>Maintain the qualifying investment and report to the relevant authority as required</li></ol>'],

            // Thai Citizen + Marriage Visa (sponsoring a foreign spouse)
            ['persona' => 'thai_citizen', 'topic' => 'marriage_visa', 'dimension' => 'who',
             'content_th' => '<p>คนไทยที่จดทะเบียนสมรสกับชาวต่างชาติ และต้องการสนับสนุนให้คู่สมรสชาวต่างชาติพำนักอยู่ในประเทศไทยได้อย่างถูกกฎหมายในระยะยาว</p>',
             'content_en' => '<p>Thai nationals who are legally married to a foreign national and wish to sponsor their foreign spouse\'s long-term, lawful stay in Thailand.</p>'],

            ['persona' => 'thai_citizen', 'topic' => 'marriage_visa', 'dimension' => 'what',
             'content_th' => '<p><strong>เอกสารที่ต้องใช้:</strong></p><ul><li>ทะเบียนสมรสไทย (คร.2/คร.3)</li><li>บัตรประชาชนและทะเบียนบ้านของคนไทย</li><li>หนังสือเดินทางของคู่สมรสชาวต่างชาติ</li><li>หลักฐานการเงิน: เงินฝากในบัญชี 400,000 บาท (ฝากไว้ก่อนยื่น 2 เดือน และคงไว้อีก 3 เดือนหลังยื่น) <em>หรือ</em> หนังสือรับรองเงินเดือน/รายได้ 40,000 บาทต่อเดือนขึ้นไป</li><li>รูปถ่ายคู่และหลักฐานความสัมพันธ์</li></ul>',
             'content_en' => '<p><strong>Required documents:</strong></p><ul><li>Thai marriage certificate</li><li>Thai citizen\'s ID card and house registration</li><li>Foreign spouse\'s passport</li><li>Financial evidence: a bank deposit of THB 400,000 (seasoned for 2 months before, and maintained for 3 months after, the application) <em>or</em> proof of monthly income of at least THB 40,000</li><li>Photos of the couple and evidence of an ongoing relationship</li></ul>'],

            ['persona' => 'thai_citizen', 'topic' => 'marriage_visa', 'dimension' => 'why',
             'content_th' => '<p>วีซ่าคู่สมรส (Non-Immigrant O) มีไว้เพื่อให้คู่สมรสชาวต่างชาติของคนไทยสามารถพำนักอยู่ด้วยกันได้อย่างถูกกฎหมาย โดยสำนักงานตรวจคนเข้าเมืองกำหนดให้ต้องพิสูจน์ว่าเป็นการสมรสที่แท้จริง และผู้สนับสนุน (ฝ่ายไทย) มีความสามารถทางการเงินเพียงพอที่จะดูแลคู่สมรสได้ เพื่อป้องกันการสมรสปลอมและภาระทางสังคม</p>',
             'content_en' => '<p>The marriage (Non-Immigrant O) visa exists so a Thai citizen\'s foreign spouse can lawfully reside together in Thailand. The Immigration Bureau requires proof of a genuine marriage and that the Thai sponsor has sufficient financial means to support the spouse, to prevent sham marriages and unsupported dependents.</p>'],

            ['persona' => 'thai_citizen', 'topic' => 'marriage_visa', 'dimension' => 'where',
             'content_th' => '<p><strong>ดำเนินการได้ที่:</strong></p><ul><li>สำนักงานเขต/อำเภอ เพื่อจดทะเบียนสมรส</li><li>สถานเอกอัครราชทูตหรือสถานกงสุลไทยในต่างประเทศ สำหรับขอวีซ่า Non-O ครั้งแรก (หากคู่สมรสยังอยู่นอกประเทศ)</li><li>สำนักงานตรวจคนเข้าเมือง ในประเทศไทย สำหรับการต่ออายุการพำนัก 1 ปี</li></ul>',
             'content_en' => '<p><strong>Where to process:</strong></p><ul><li>District/Amphur Office, to register the marriage</li><li>Thai Embassy or Consulate abroad, for the initial Non-O visa (if the spouse is still outside Thailand)</li><li>Immigration Bureau in Thailand, for the annual 1-year extension of stay</li></ul>'],

            ['persona' => 'thai_citizen', 'topic' => 'marriage_visa', 'dimension' => 'when',
             'content_th' => '<p>วีซ่า Non-O เบื้องต้นมีอายุ 90 วัน จากนั้นต่ออายุการพำนักได้ครั้งละ <strong>1 ปี</strong> หลักฐานเงินฝาก 400,000 บาท ต้องฝากไว้ก่อนยื่นคำขอ 2 เดือน และคงไว้อีก 3 เดือนหลังยื่น ควรเริ่มเตรียมเอกสารต่ออายุล่วงหน้าอย่างน้อย 45 วันก่อนวันครบกำหนด</p>',
             'content_en' => '<p>The initial Non-O visa is valid for 90 days, after which the extension of stay can be renewed <strong>1 year</strong> at a time. The THB 400,000 deposit must be seasoned for 2 months before, and maintained for 3 months after, the application. Begin preparing renewal documents at least 45 days before the current permission expires.</p>'],

            ['persona' => 'thai_citizen', 'topic' => 'marriage_visa', 'dimension' => 'how',
             'content_th' => '<ol><li>จดทะเบียนสมรสที่สำนักงานเขต/อำเภอ และขอใบสำคัญการสมรส</li><li>เตรียมหลักฐานการเงิน (เงินฝาก 400,000 บาท หรือหนังสือรับรองรายได้)</li><li>ยื่นขอวีซ่า Non-Immigrant O ที่สถานทูตไทย หรือเปลี่ยนประเภทวีซ่าในประเทศหากมีสิทธิ์</li><li>ยื่นคำขอขยายระยะเวลาพำนัก 1 ปี ที่สำนักงานตรวจคนเข้าเมือง ก่อนวีซ่าเดิมหมดอายุ</li><li>แจ้ง TM30 หากเปลี่ยนที่อยู่ และให้คู่สมรสรายงานตัว 90 วันตามกำหนด</li><li>ต่ออายุทุกปีพร้อมหลักฐานการเงินที่เป็นปัจจุบัน</li></ol>',
             'content_en' => '<ol><li>Register the marriage at the District/Amphur Office and obtain the marriage certificate</li><li>Prepare financial evidence (the THB 400,000 deposit or an income certificate)</li><li>Apply for the Non-Immigrant O visa at a Thai embassy, or convert status in-country if eligible</li><li>Apply for the 1-year extension of stay at the Immigration Bureau before the current permission expires</li><li>File TM30 if the address changes, and ensure the spouse files 90-day reports on schedule</li><li>Renew annually with up-to-date financial evidence</li></ol>'],

            // Officer + TM30 (verification / processing side)
            ['persona' => 'officer', 'topic' => 'tm30', 'dimension' => 'who',
             'content_th' => '<p>เจ้าพนักงานตรวจคนเข้าเมืองประจำสำนักงานในพื้นที่ ที่มีหน้าที่รับและตรวจสอบการแจ้งที่พักอาศัยของคนต่างด้าว (TM30) ที่ยื่นโดยเจ้าบ้าน โรงแรม หรือนายจ้าง</p>',
             'content_en' => '<p>Immigration officers at local Immigration Bureau offices responsible for receiving and verifying TM30 accommodation notifications submitted by house owners, hotels, or employers.</p>'],

            ['persona' => 'officer', 'topic' => 'tm30', 'dimension' => 'what',
             'content_th' => '<p><strong>สิ่งที่ต้องตรวจสอบ:</strong></p><ul><li>แบบฟอร์ม TM30 ที่ยื่นมา</li><li>สำเนาหนังสือเดินทางและตราประทับวีซ่าของคนต่างด้าว</li><li>ที่อยู่ที่พักตรงกับที่จดทะเบียนหรือไม่</li><li>ข้อมูลตรงกับบัตร ตม.6 (ขาเข้า-ขาออก) หรือไม่</li><li>สถานะวีซ่าของคนต่างด้าวยังไม่หมดอายุ</li></ul>',
             'content_en' => '<p><strong>What to verify:</strong></p><ul><li>The submitted TM30 form</li><li>A copy of the foreigner\'s passport and visa/entry stamp</li><li>Whether the accommodation address matches the registered premises</li><li>Whether the details match the TM6 arrival/departure card</li><li>That the foreigner\'s visa status has not expired</li></ul>'],

            ['persona' => 'officer', 'topic' => 'tm30', 'dimension' => 'why',
             'content_th' => '<p>การตรวจสอบความถูกต้องของแบบ TM30 เป็นการปฏิบัติหน้าที่ตามมาตรา 38 แห่งพระราชบัญญัติคนเข้าเมือง พ.ศ. 2522 เพื่อให้ฐานข้อมูลที่อยู่ของคนต่างด้าวในประเทศมีความถูกต้องและเป็นปัจจุบัน ซึ่งสนับสนุนงานด้านความมั่นคงและการตอบสนองในกรณีฉุกเฉิน</p>',
             'content_en' => '<p>Verifying TM30 submissions fulfills the Immigration Bureau\'s duty under Section 38 of the Immigration Act B.E. 2522 (1979) to maintain an accurate, up-to-date record of where foreign nationals reside in the country, supporting national security and emergency response.</p>'],

            ['persona' => 'officer', 'topic' => 'tm30', 'dimension' => 'where',
             'content_th' => '<p><strong>ดำเนินการที่:</strong></p><ul><li>เคาน์เตอร์บริการ TM30 ของสำนักงานตรวจคนเข้าเมืองในพื้นที่</li><li>ระบบหลังบ้านของ tm30.immigration.go.th สำหรับคำขอที่ยื่นออนไลน์</li></ul>',
             'content_en' => '<p><strong>Where this is processed:</strong></p><ul><li>The TM30 service counter at the local Immigration Bureau office</li><li>The back-office admin panel of tm30.immigration.go.th for online submissions</li></ul>'],

            ['persona' => 'officer', 'topic' => 'tm30', 'dimension' => 'when',
             'content_th' => '<p>ควรตรวจสอบและบันทึกข้อมูลการแจ้งภายในวันที่ได้รับคำขอ เนื่องจากกฎหมายกำหนดให้ผู้แจ้งต้องยื่นภายใน 24 ชั่วโมงหลังคนต่างด้าวเข้าพัก การประมวลผลที่รวดเร็วช่วยให้ฐานข้อมูลเป็นปัจจุบันเสมอ</p>',
             'content_en' => '<p>Notifications should be verified and logged the same day they are received, since the law requires the notifier to file within 24 hours of the foreigner\'s check-in. Prompt processing keeps the database current.</p>'],

            ['persona' => 'officer', 'topic' => 'tm30', 'dimension' => 'how',
             'content_th' => '<ol><li>รับคำขอแจ้ง TM30 (ที่เคาน์เตอร์หรือผ่านระบบออนไลน์)</li><li>ตรวจสอบตัวตนและอำนาจของผู้แจ้ง (เจ้าบ้าน/ผู้จัดการโรงแรม/นายจ้าง)</li><li>ตรวจสอบข้อมูลหนังสือเดินทางและสถานะวีซ่าของคนต่างด้าว</li><li>บันทึกข้อมูลการแจ้งเข้าสู่ระบบฐานข้อมูล</li><li>ออกใบรับแจ้งให้ผู้ยื่นคำขอ</li><li>หากพบข้อมูลไม่ตรงกัน (เช่น วีซ่าหมดอายุ) ให้บันทึกและส่งต่อเพื่อดำเนินการตามขั้นตอน</li></ol>',
             'content_en' => '<ol><li>Receive the TM30 submission (in person or via the online system)</li><li>Verify the submitter\'s identity and authority (house owner, hotel manager, or employer)</li><li>Check the foreigner\'s passport details and visa status</li><li>Record the notification in the database</li><li>Issue an acknowledgement receipt to the submitter</li><li>If discrepancies are found (e.g. an expired visa), log them and refer the case for follow-up action</li></ol>'],
        ];

        foreach ($entries as $data) {
            SmartGuide::updateOrCreate(
                ['persona' => $data['persona'], 'topic' => $data['topic'], 'dimension' => $data['dimension']],
                array_merge($data, ['is_active' => true])
            );
        }

        $this->command->info('✅ Smart guide entries seeded: ' . count($entries));
    }
}
