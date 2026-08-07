<?php

namespace Database\Seeders;

use App\Models\DashboardCategoryStat;
use App\Models\DashboardKpi;
use App\Models\DashboardMonthlyTrend;
use App\Models\DashboardNationalityStat;
use App\Models\DashboardPeriod;
use App\Models\DashboardProvincialStat;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Period: June 2026 (latest) ───────────────────────────
        $period = DashboardPeriod::updateOrCreate(
            ['period_type' => 'monthly', 'period_date' => '2026-06-01'],
            ['label_th' => 'มิถุนายน 2569', 'label_en' => 'June 2026', 'is_latest' => true]
        );

        // ─── KPIs ─────────────────────────────────────────────────
        $kpis = [
            ['metric_key' => 'total_foreigners',    'value' => 256842, 'previous_value' => 228418, 'unit' => 'count'],
            ['metric_key' => 'overstay_count',       'value' => 2153,   'previous_value' => 1987,   'unit' => 'count'],
            ['metric_key' => 'tm30_compliance_rate', 'value' => 92.6,   'previous_value' => 90.1,   'unit' => 'percent'],
            ['metric_key' => 'risk_alert_count',     'value' => 1234,   'previous_value' => 1156,   'unit' => 'count'],
        ];

        foreach ($kpis as $kpi) {
            DashboardKpi::updateOrCreate(
                ['period_id' => $period->id, 'metric_key' => $kpi['metric_key']],
                $kpi
            );
        }

        // ─── Top Nationalities ────────────────────────────────────
        $nationalities = [
            ['code' => 'CN', 'th' => 'จีน',         'en' => 'China',     'zh' => '中国',   'count' => 45213, 'pct' => 17.6],
            ['code' => 'MM', 'th' => 'เมียนมา',      'en' => 'Myanmar',   'zh' => '缅甸',   'count' => 32114, 'pct' => 12.5],
            ['code' => 'LA', 'th' => 'ลาว',          'en' => 'Laos',      'zh' => '老挝',   'count' => 21242, 'pct' => 8.3],
            ['code' => 'VN', 'th' => 'เวียดนาม',     'en' => 'Vietnam',   'zh' => '越南',   'count' => 18766, 'pct' => 7.3],
            ['code' => 'KH', 'th' => 'กัมพูชา',      'en' => 'Cambodia',  'zh' => '柬埔寨', 'count' => 17852, 'pct' => 7.0],
            ['code' => 'JP', 'th' => 'ญี่ปุ่น',      'en' => 'Japan',     'zh' => '日本',   'count' => 14530, 'pct' => 5.7],
            ['code' => 'KR', 'th' => 'เกาหลีใต้',    'en' => 'South Korea','zh' => '韩国',  'count' => 12890, 'pct' => 5.0],
            ['code' => 'IN', 'th' => 'อินเดีย',       'en' => 'India',     'zh' => '印度',   'count' => 11200, 'pct' => 4.4],
            ['code' => 'US', 'th' => 'สหรัฐอเมริกา', 'en' => 'USA',       'zh' => '美国',   'count' => 9840,  'pct' => 3.8],
            ['code' => 'RU', 'th' => 'รัสเซีย',       'en' => 'Russia',    'zh' => '俄罗斯', 'count' => 8760,  'pct' => 3.4],
        ];

        $period->nationalityStats()->delete();
        foreach ($nationalities as $rank => $n) {
            DashboardNationalityStat::create([
                'period_id'            => $period->id,
                'nationality_code'     => $n['code'],
                'nationality_name_th'  => $n['th'],
                'nationality_name_en'  => $n['en'],
                'nationality_name_zh'  => $n['zh'],
                'count'                => $n['count'],
                'rank'                 => $rank + 1,
                'percentage'           => $n['pct'],
            ]);
        }

        // ─── Immigration Categories ───────────────────────────────
        $categories = [
            ['category' => 'tourist',  'label_th' => 'นักท่องเที่ยว',      'label_en' => 'Tourist',  'label_zh' => '旅游',   'count' => 115579, 'percentage' => 45.0, 'sort_order' => 0],
            ['category' => 'worker',   'label_th' => 'แรงงาน',             'label_en' => 'Worker',   'label_zh' => '劳工',   'count' => 51368,  'percentage' => 20.0, 'sort_order' => 1],
            ['category' => 'overstay', 'label_th' => 'อยู่เกินกำหนด',      'label_en' => 'Overstay', 'label_zh' => '逾期',   'count' => 30821,  'percentage' => 12.0, 'sort_order' => 2],
            ['category' => 'student',  'label_th' => 'นักเรียน/นักศึกษา',  'label_en' => 'Student',  'label_zh' => '学生',   'count' => 20547,  'percentage' => 8.0,  'sort_order' => 3],
            ['category' => 'investor', 'label_th' => 'นักลงทุน',           'label_en' => 'Investor', 'label_zh' => '投资者', 'count' => 12842,  'percentage' => 5.0,  'sort_order' => 4],
            ['category' => 'other',    'label_th' => 'อื่น ๆ',             'label_en' => 'Other',    'label_zh' => '其他',   'count' => 25685,  'percentage' => 10.0, 'sort_order' => 5],
        ];

        $period->categoryStats()->delete();
        foreach ($categories as $cat) {
            DashboardCategoryStat::create(array_merge($cat, ['period_id' => $period->id]));
        }

        // ─── Monthly Trends (2026) ────────────────────────────────
        $monthsTH = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
        $monthsEN = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        $trendData = [
            [180000, 175000, 1800, 16000, 14500, 1100],
            [195000, 188000, 1850, 16800, 15200, 1050],
            [210000, 202000, 1900, 17200, 15700, 1080],
            [225000, 218000, 1950, 17800, 16100, 1120],
            [240000, 232000, 2050, 18200, 16600, 1180],
            [256842, 248000, 2153, 18600, 17200, 1234],
            [0, 0, 0, 0, 0, 0], // future months
            [0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0],
        ];

        foreach ($trendData as $i => $row) {
            if ($row[0] === 0) continue; // skip future months
            DashboardMonthlyTrend::updateOrCreate(
                ['year' => 2026, 'month' => $i + 1],
                [
                    'month_label_th'   => $monthsTH[$i],
                    'month_label_en'   => $monthsEN[$i],
                    'total_arrivals'   => $row[0],
                    'total_departures' => $row[1],
                    'overstay_count'   => $row[2],
                    'tm30_count'       => $row[3],
                    'tm30_compliant'   => $row[4],
                    'risk_alert_count' => $row[5],
                ]
            );
        }

        // ─── Provincial Stats ─────────────────────────────────────
        $period->provincialStats()->delete();

        $provinces = [
            // Bangkok & Central
            ['BKK', 'กรุงเทพมหานคร',  'Bangkok',          'ภาคกลาง', 'Central',   85000, 520, 8200, 96.5, 'low',    0],
            ['NBI', 'นนทบุรี',         'Nonthaburi',       'ภาคกลาง', 'Central',   15200, 180, 1450, 94.2, 'low',    1],
            ['PTE', 'ปทุมธานี',        'Pathum Thani',     'ภาคกลาง', 'Central',   12800, 155, 1200, 93.1, 'low',    2],
            // North
            ['CNX', 'เชียงใหม่',       'Chiang Mai',       'ภาคเหนือ', 'North',    18500, 310, 1780, 89.2, 'medium', 3],
            ['CCO', 'เชียงราย',        'Chiang Rai',       'ภาคเหนือ', 'North',     9800, 420, 920,  82.4, 'high',   4],
            // Northeast
            ['KKN', 'ขอนแก่น',        'Khon Kaen',        'ภาคตะวันออกเฉียงเหนือ', 'Northeast', 8200, 145, 780, 91.5, 'low', 5],
            ['UDN', 'อุดรธานี',        'Udon Thani',       'ภาคตะวันออกเฉียงเหนือ', 'Northeast', 7600, 195, 720, 88.9, 'medium', 6],
            // East
            ['CBI', 'ชลบุรี (พัทยา)',  'Chonburi (Pattaya)', 'ภาคตะวันออก', 'East', 32000, 185, 3100, 95.8, 'low',  7],
            // South
            ['PKT', 'ภูเก็ต',          'Phuket',           'ภาคใต้',  'South',    42000, 98,  4200, 97.3, 'low',    8],
            ['HYI', 'หาดใหญ่/สงขลา',  'Hat Yai / Songkhla', 'ภาคใต้', 'South',  14500, 265, 1380, 86.5, 'high',   9],
            ['KBI', 'กระบี่',          'Krabi',            'ภาคใต้',  'South',    12800, 88,  1260, 94.1, 'low',   10],
        ];

        foreach ($provinces as $p) {
            DashboardProvincialStat::create([
                'period_id'           => $period->id,
                'province_code'       => $p[0],
                'province_name_th'    => $p[1],
                'province_name_en'    => $p[2],
                'region_th'           => $p[3],
                'region_en'           => $p[4],
                'total_foreigners'    => $p[5],
                'overstay_count'      => $p[6],
                'tm30_count'          => $p[7],
                'tm30_compliance_rate'=> $p[8],
                'risk_level'          => $p[9],
                'sort_order'          => $p[10],
            ]);
        }
    }
}
