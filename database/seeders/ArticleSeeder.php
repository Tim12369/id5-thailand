<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $newsCount        = 0;
        $announcementCount = 0;
        $alertCount       = 0;
        $kbCount          = 0;

        // ── News articles ────────────────────────────────────────────
        $newsCat = Category::where('type', 'news')->where('slug', 'press-release')->first();

        $newsItems = [
            [
                'title_th'   => 'กองบังคับการตรวจคนเข้าเมือง 5 เปิดตัวระบบ ID5 @ Thailand 5.0',
                'title_en'   => 'Immigration Division 5 Launches ID5 @ Thailand 5.0 Platform',
                'excerpt_th' => 'ระบบดิจิทัลใหม่สำหรับการบริการตรวจคนเข้าเมืองครบวงจร',
                'excerpt_en' => 'New digital platform for comprehensive immigration services',
                'body_th'    => '<p>กองบังคับการตรวจคนเข้าเมือง 5 เปิดตัวระบบ ID5 @ Thailand 5.0 เพื่อให้บริการประชาชนและชาวต่างชาติอย่างครบวงจร ภายใต้แนวคิด One Immigration · One Platform · Multiple Stakeholders · Two Experiences</p><p>ระบบดังกล่าวประกอบด้วย Public Portal สำหรับประชาชน, Internal Portal สำหรับเจ้าหน้าที่, Line OA สำหรับบริการผ่านมือถือ และ Executive Dashboard สำหรับผู้บริหาร</p>',
                'body_en'    => '<p>Immigration Division 5 launches ID5 @ Thailand 5.0, a comprehensive digital platform for the public and foreign nationals under the concept One Immigration · One Platform · Multiple Stakeholders · Two Experiences.</p>',
                'is_pinned'  => true,
            ],
            [
                'title_th'   => 'ปรับปรุงกระบวนการรายงานตัว 90 วัน สะดวกขึ้น',
                'title_en'   => 'Improved 90-Day Reporting Process Now Available',
                'excerpt_th' => 'สามารถรายงานตัวออนไลน์ได้สะดวกขึ้น ลดขั้นตอน',
                'excerpt_en' => 'Streamlined online 90-day reporting process',
                'body_th'    => '<p>ตรวจคนเข้าเมืองปรับปรุงระบบการรายงานตัว 90 วันออนไลน์ให้สะดวกรวดเร็วขึ้น</p>',
                'body_en'    => '<p>Immigration has improved the online 90-day reporting system for greater convenience.</p>',
            ],
        ];

        foreach ($newsItems as $item) {
            Article::create([
                'type'        => 'news',
                'category_id' => $newsCat?->id,
                'title_th'    => $item['title_th'],
                'title_en'    => $item['title_en'],
                'excerpt_th'  => $item['excerpt_th'],
                'excerpt_en'  => $item['excerpt_en'],
                'body_th'     => $item['body_th'],
                'body_en'     => $item['body_en'],
                'slug'        => Str::slug($item['title_en']) . '-' . rand(1000, 9999),
                'status'      => 'published',
                'is_public'   => true,
                'is_pinned'   => $item['is_pinned'] ?? false,
                'published_at'=> now()->subDays(rand(1, 30)),
            ]);
            $newsCount++;
        }

        // ── Announcements ────────────────────────────────────────────
        $announcements = [
            [
                'title_th' => 'ประกาศ: ขอความร่วมมือแจ้ง TM30 ตามกำหนดเวลา',
                'title_en' => 'Notice: Please File TM30 Within the Required Timeframe',
                'body_th'  => '<p>ขอความร่วมมือเจ้าของที่พักทุกประเภทแจ้งที่พักคนต่างด้าว (TM30) ภายใน 24 ชั่วโมง เพื่อปฏิบัติตามกฎหมาย</p>',
                'body_en'  => '<p>All accommodation providers are requested to file TM30 within 24 hours of a foreign national\'s arrival to comply with the law.</p>',
            ],
            [
                'title_th' => 'แจ้งวันหยุดทำการประจำปี 2567',
                'title_en' => 'Office Closure Notice — Public Holidays 2024',
                'body_th'  => '<p>สำนักงานตรวจคนเข้าเมืองจะปิดทำการในวันหยุดนักขัตฤกษ์ประจำปี 2567 ตามประกาศรัฐบาล</p>',
                'body_en'  => '<p>Immigration offices will be closed on official public holidays as announced by the government.</p>',
            ],
        ];

        foreach ($announcements as $item) {
            Article::create([
                'type'        => 'announcement',
                'title_th'    => $item['title_th'],
                'title_en'    => $item['title_en'],
                'body_th'     => $item['body_th'],
                'body_en'     => $item['body_en'],
                'slug'        => Str::slug($item['title_en']) . '-' . rand(1000, 9999),
                'status'      => 'published',
                'is_public'   => true,
                'published_at'=> now()->subDays(rand(1, 14)),
            ]);
            $announcementCount++;
        }

        // ── Alerts ───────────────────────────────────────────────────
        $alerts = [
            [
                'title_th'    => 'ระวัง! ผู้แอบอ้างเป็นเจ้าหน้าที่ ตม.',
                'title_en'    => 'Warning: Individuals Impersonating Immigration Officers',
                'excerpt_th'  => 'มีการรายงานบุคคลที่แอบอ้างเป็นเจ้าหน้าที่ ตม. เรียกรับเงิน',
                'excerpt_en'  => 'Reports of individuals impersonating immigration officers and requesting payment',
                'body_th'     => '<p>มีรายงานบุคคลที่แอบอ้างเป็นเจ้าหน้าที่ตรวจคนเข้าเมืองและเรียกรับเงินจากชาวต่างชาติ กรุณาระวังและรายงานพฤติกรรมดังกล่าวที่สายด่วน 1178</p>',
                'body_en'     => '<p>There are reports of individuals impersonating immigration officers and requesting payment from foreign nationals. Please be cautious and report such behavior to hotline 1178.</p>',
                'alert_level' => 'danger',
            ],
        ];

        foreach ($alerts as $item) {
            Article::create([
                'type'        => 'alert',
                'title_th'    => $item['title_th'],
                'title_en'    => $item['title_en'],
                'excerpt_th'  => $item['excerpt_th'] ?? null,
                'excerpt_en'  => $item['excerpt_en'] ?? null,
                'body_th'     => $item['body_th'],
                'body_en'     => $item['body_en'],
                'slug'        => Str::slug($item['title_en']) . '-' . rand(1000, 9999),
                'status'      => 'published',
                'is_public'   => true,
                'alert_level' => $item['alert_level'],
                'published_at'=> now()->subDays(rand(1, 7)),
            ]);
            $alertCount++;
        }

        // ── Public KB Articles ────────────────────────────────────────
        $visaKbCat = Category::where('type', 'kb_public')->where('slug', 'visa')->first();
        $tm30KbCat = Category::where('type', 'kb_public')->where('slug', 'tm30')->first();

        $kbItems = [
            [
                'category_id' => $visaKbCat?->id,
                'title_th'    => 'คู่มือวีซ่าท่องเที่ยวสำหรับนักเดินทางทุกสัญชาติ',
                'title_en'    => 'Tourist Visa Guide for All Nationalities',
                'excerpt_th'  => 'ข้อมูลวีซ่าท่องเที่ยวประเทศไทยครบวงจร',
                'excerpt_en'  => 'Complete guide to Thailand tourist visa',
                'body_th'     => '<h2>วีซ่าท่องเที่ยว (Tourist Visa)</h2><p>วีซ่าท่องเที่ยวอนุญาตให้พำนักในประเทศไทยได้ 60 วัน</p><h3>คุณสมบัติ</h3><ul><li>หนังสือเดินทางที่มีอายุเหลืออย่างน้อย 18 เดือน</li><li>มีเงินทุนเพียงพอ</li><li>มีตั๋วกลับ</li></ul>',
                'body_en'     => '<h2>Tourist Visa (TR)</h2><p>A tourist visa allows you to stay in Thailand for 60 days.</p><h3>Requirements</h3><ul><li>Passport valid for at least 18 months</li><li>Sufficient funds</li><li>Return ticket</li></ul>',
            ],
            [
                'category_id' => $tm30KbCat?->id,
                'title_th'    => 'คู่มือ TM30 ฉบับสมบูรณ์ — ใครต้องยื่น เมื่อไร อย่างไร',
                'title_en'    => 'Complete TM30 Guide — Who Must File, When, and How',
                'excerpt_th'  => 'คู่มือครบถ้วนสำหรับการยื่น TM30',
                'excerpt_en'  => 'Comprehensive guide to filing TM30',
                'body_th'     => '<h2>TM30 คืออะไร?</h2><p>แบบแจ้งที่พักอาศัยของคนต่างด้าว ตาม พ.ร.บ. คนเข้าเมือง มาตรา 38</p><h3>ใครต้องยื่น?</h3><ul><li>เจ้าของโรงแรม</li><li>เจ้าของบ้านเช่า</li><li>นายจ้างที่ให้ที่พัก</li></ul><h3>กำหนดเวลา</h3><p>ภายใน 24 ชั่วโมงหลังจากคนต่างด้าวมาพัก</p>',
                'body_en'     => '<h2>What is TM30?</h2><p>A notification of foreigner residence under the Immigration Act Section 38.</p><h3>Who must file?</h3><ul><li>Hotel owners</li><li>Landlords</li><li>Employers providing accommodation</li></ul><h3>Deadline</h3><p>Within 24 hours of the foreigner\'s arrival.</p>',
            ],
        ];

        foreach ($kbItems as $item) {
            Article::create([
                'type'        => 'kb_public',
                'category_id' => $item['category_id'],
                'title_th'    => $item['title_th'],
                'title_en'    => $item['title_en'],
                'excerpt_th'  => $item['excerpt_th'],
                'excerpt_en'  => $item['excerpt_en'],
                'body_th'     => $item['body_th'],
                'body_en'     => $item['body_en'],
                'slug'        => Str::slug($item['title_en']) . '-' . rand(1000, 9999),
                'status'      => 'published',
                'is_public'   => true,
                'published_at'=> now()->subDays(rand(1, 60)),
            ]);
            $kbCount++;
        }

        $this->command->info("✅ Articles seeded: {$newsCount} news, {$announcementCount} announcements, {$alertCount} alerts, {$kbCount} KB articles");
    }
}
