<?php

namespace Database\Seeders;

use App\Models\Station;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StationSeeder extends Seeder
{
    public function run(): void
    {
        $hours = ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'];
        $hours24 = ['mon'=>'00:00-23:59','tue'=>'00:00-23:59','wed'=>'00:00-23:59','thu'=>'00:00-23:59','fri'=>'00:00-23:59','sat'=>'00:00-23:59','sun'=>'00:00-23:59'];

        $stations = [
            // ── ภาคเหนือ (North) ───────────────────────────────────────
            [
                'code' => 'CM01', 'province' => 'เชียงใหม่', 'region' => 'ภาคเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดเชียงใหม่',
                'name_en' => 'Chiang Mai Immigration Office',
                'address_th' => '71 ถ.แสนพิทักษ์ ต.ช้างเผือก อ.เมือง จ.เชียงใหม่ 50300',
                'address_en' => '71 Sanphitак Rd, Chang Phueak, Mueang, Chiang Mai 50300',
                'phone' => '053-201-755', 'email' => 'immigration.cm@go.th',
                'lat' => 18.7961, 'lng' => 98.9795,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 1,
            ],
            [
                'code' => 'CR01', 'province' => 'เชียงราย', 'region' => 'ภาคเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดเชียงราย',
                'name_en' => 'Chiang Rai Immigration Office',
                'address_th' => 'ถ.สิงหไคล ต.เวียง อ.เมือง จ.เชียงราย 57000',
                'address_en' => 'Singhaklai Rd, Wiang, Mueang, Chiang Rai 57000',
                'phone' => '053-714-266', 'email' => null,
                'lat' => 19.9071, 'lng' => 99.8305,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 2,
            ],
            [
                'code' => 'LP01', 'province' => 'ลำปาง', 'region' => 'ภาคเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดลำปาง',
                'name_en' => 'Lampang Immigration Office',
                'address_th' => 'ถ.ประสานไมตรี ต.สบตุ๋ย อ.เมือง จ.ลำปาง 52100',
                'address_en' => 'Prasan Maitri Rd, Sop Tui, Mueang, Lampang 52100',
                'phone' => '054-217-090', 'email' => null,
                'lat' => 18.2894, 'lng' => 99.4933,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 3,
            ],
            [
                'code' => 'LN01', 'province' => 'ลำพูน', 'region' => 'ภาคเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดลำพูน',
                'name_en' => 'Lamphun Immigration Office',
                'address_th' => 'ถ.อินทยงยศ ต.ในเมือง อ.เมือง จ.ลำพูน 51000',
                'address_en' => 'Inthayongyot Rd, Nai Mueang, Mueang, Lamphun 51000',
                'phone' => '053-511-044', 'email' => null,
                'lat' => 18.5746, 'lng' => 99.0085,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 4,
            ],
            [
                'code' => 'MH01', 'province' => 'แม่ฮ่องสอน', 'region' => 'ภาคเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดแม่ฮ่องสอน',
                'name_en' => 'Mae Hong Son Immigration Office',
                'address_th' => 'ถ.ขุนลุมประพาส ต.จองคำ อ.เมือง จ.แม่ฮ่องสอน 58000',
                'address_en' => 'Khun Lum Praphat Rd, Chong Kham, Mueang, Mae Hong Son 58000',
                'phone' => '053-612-094', 'email' => null,
                'lat' => 19.3020, 'lng' => 97.9654,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 5,
            ],
            [
                'code' => 'NAN01', 'province' => 'น่าน', 'region' => 'ภาคเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดน่าน',
                'name_en' => 'Nan Immigration Office',
                'address_th' => 'ถ.สุมนเทวราช ต.ในเวียง อ.เมือง จ.น่าน 55000',
                'address_en' => 'Sumon Thewarat Rd, Nai Wiang, Mueang, Nan 55000',
                'phone' => '054-710-256', 'email' => null,
                'lat' => 18.7756, 'lng' => 100.7729,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 6,
            ],
            [
                'code' => 'PY01', 'province' => 'พะเยา', 'region' => 'ภาคเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดพะเยา',
                'name_en' => 'Phayao Immigration Office',
                'address_th' => 'ถ.ประตูชัย ต.เวียง อ.เมือง จ.พะเยา 56000',
                'address_en' => 'Pratu Chai Rd, Wiang, Mueang, Phayao 56000',
                'phone' => '054-431-032', 'email' => null,
                'lat' => 19.1664, 'lng' => 99.9027,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 7,
            ],
            [
                'code' => 'PL01', 'province' => 'แพร่', 'region' => 'ภาคเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดแพร่',
                'name_en' => 'Phrae Immigration Office',
                'address_th' => 'ถ.ยันตรกิจโกศล ต.ในเวียง อ.เมือง จ.แพร่ 54000',
                'address_en' => 'Yantarakit Koson Rd, Nai Wiang, Mueang, Phrae 54000',
                'phone' => '054-511-045', 'email' => null,
                'lat' => 18.1447, 'lng' => 100.1412,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 8,
            ],

            // ── ภาคกลาง (Central / Bangkok) ────────────────────────────
            [
                'code' => 'BKK01', 'province' => 'กรุงเทพมหานคร', 'region' => 'ภาคกลาง',
                'name_th' => 'สำนักงานตรวจคนเข้าเมือง (ศูนย์ราชการเฉลิมพระเกียรติฯ แจ้งวัฒนะ)',
                'name_en' => 'Immigration Bureau — Government Complex, Chaeng Watthana',
                'address_th' => '120 หมู่ 3 ถ.แจ้งวัฒนะ ต.ทุ่งสองห้อง อ.หลักสี่ กรุงเทพมหานคร 10210',
                'address_en' => '120 Moo 3, Chaeng Watthana Rd, Thung Song Hong, Laksi, Bangkok 10210',
                'phone' => '02-141-9889', 'email' => 'immigration.bkk@go.th',
                'lat' => 13.9001, 'lng' => 100.5635,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 9,
            ],
            [
                'code' => 'BKK02', 'province' => 'กรุงเทพมหานคร', 'region' => 'ภาคกลาง',
                'name_th' => 'ตรวจคนเข้าเมือง ท่าอากาศยานสุวรรณภูมิ',
                'name_en' => 'Suvarnabhumi Airport Immigration Office',
                'address_th' => '999 หมู่ 1 ทางหลวงพิเศษหมายเลข 7 ต.หนองปรือ อ.บางพลี จ.สมุทรปราการ 10540',
                'address_en' => '999 Moo 1, Motorway 7, Nong Prue, Bang Phli, Samut Prakan 10540',
                'phone' => '02-134-0151', 'email' => null,
                'lat' => 13.6900, 'lng' => 100.7501,
                'opening_hours' => ['mon'=>'00:00-23:59','tue'=>'00:00-23:59','wed'=>'00:00-23:59','thu'=>'00:00-23:59','fri'=>'00:00-23:59','sat'=>'00:00-23:59','sun'=>'00:00-23:59'],
                'sort_order' => 10,
            ],
            [
                'code' => 'KAN01', 'province' => 'กาญจนบุรี', 'region' => 'ภาคกลาง',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดกาญจนบุรี',
                'name_en' => 'Kanchanaburi Immigration Office',
                'address_th' => 'ถ.แสงชูโต ต.บ้านเหนือ อ.เมือง จ.กาญจนบุรี 71000',
                'address_en' => 'Saeng Chuto Rd, Ban Nuea, Mueang, Kanchanaburi 71000',
                'phone' => '034-512-090', 'email' => null,
                'lat' => 14.0227, 'lng' => 99.5328,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 11,
            ],

            // ── ภาคตะวันออก (East) ─────────────────────────────────────
            [
                'code' => 'CB01', 'province' => 'ชลบุรี', 'region' => 'ภาคตะวันออก',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดชลบุรี (พัทยา)',
                'name_en' => 'Chonburi (Pattaya) Immigration Office',
                'address_th' => '75/265 หมู่ 12 ถ.พัทยาสาย 2 ต.หนองปรือ อ.บางละมุง จ.ชลบุรี 20150',
                'address_en' => '75/265 Moo 12, Pattaya 2nd Rd, Nong Prue, Banglamung, Chonburi 20150',
                'phone' => '038-252-751', 'email' => 'immigration.pattaya@go.th',
                'lat' => 12.9357, 'lng' => 100.8907,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 12,
            ],
            [
                'code' => 'RY01', 'province' => 'ระยอง', 'region' => 'ภาคตะวันออก',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดระยอง',
                'name_en' => 'Rayong Immigration Office',
                'address_th' => 'ถ.สุขุมวิท ต.เนินพระ อ.เมือง จ.ระยอง 21000',
                'address_en' => 'Sukhumvit Rd, Noen Phra, Mueang, Rayong 21000',
                'phone' => '038-863-027', 'email' => null,
                'lat' => 12.6638, 'lng' => 101.2444,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 13,
            ],

            // ── ภาคตะวันออกเฉียงเหนือ (Northeast / Isan) ───────────────
            [
                'code' => 'KK01', 'province' => 'ขอนแก่น', 'region' => 'ภาคตะวันออกเฉียงเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดขอนแก่น',
                'name_en' => 'Khon Kaen Immigration Office',
                'address_th' => 'ถ.มิตรภาพ ต.ในเมือง อ.เมือง จ.ขอนแก่น 40000',
                'address_en' => 'Mittraphap Rd, Nai Mueang, Mueang, Khon Kaen 40000',
                'phone' => '043-227-505', 'email' => null,
                'lat' => 16.4419, 'lng' => 102.8359,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 14,
            ],
            [
                'code' => 'NK01', 'province' => 'นครราชสีมา', 'region' => 'ภาคตะวันออกเฉียงเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดนครราชสีมา',
                'name_en' => 'Nakhon Ratchasima Immigration Office',
                'address_th' => 'ถ.มิตรภาพ ต.ในเมือง อ.เมือง จ.นครราชสีมา 30000',
                'address_en' => 'Mittraphap Rd, Nai Mueang, Mueang, Nakhon Ratchasima 30000',
                'phone' => '044-243-959', 'email' => null,
                'lat' => 14.9799, 'lng' => 102.0978,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 15,
            ],
            [
                'code' => 'UD01', 'province' => 'อุดรธานี', 'region' => 'ภาคตะวันออกเฉียงเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดอุดรธานี',
                'name_en' => 'Udon Thani Immigration Office',
                'address_th' => 'ถ.โพศรี ต.หมากแข้ง อ.เมือง จ.อุดรธานี 41000',
                'address_en' => 'Pho Si Rd, Mak Khaeng, Mueang, Udon Thani 41000',
                'phone' => '042-243-378', 'email' => null,
                'lat' => 17.4156, 'lng' => 102.7872,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 16,
            ],
            [
                'code' => 'UB01', 'province' => 'อุบลราชธานี', 'region' => 'ภาคตะวันออกเฉียงเหนือ',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดอุบลราชธานี',
                'name_en' => 'Ubon Ratchathani Immigration Office',
                'address_th' => 'ถ.แจ้งสนิท ต.ในเมือง อ.เมือง จ.อุบลราชธานี 34000',
                'address_en' => 'Chaeng Sanit Rd, Nai Mueang, Mueang, Ubon Ratchathani 34000',
                'phone' => '045-243-301', 'email' => null,
                'lat' => 15.2287, 'lng' => 104.8568,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 17,
            ],

            // ── ภาคใต้ (South) ──────────────────────────────────────────
            [
                'code' => 'PK01', 'province' => 'ภูเก็ต', 'region' => 'ภาคใต้',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดภูเก็ต',
                'name_en' => 'Phuket Immigration Office',
                'address_th' => '482 ถ.พศจิรเดช ต.วิชิต อ.เมือง จ.ภูเก็ต 83000',
                'address_en' => '482 Phasajiradet Rd, Wichit, Mueang, Phuket 83000',
                'phone' => '076-221-905', 'email' => 'immigration.phuket@go.th',
                'lat' => 7.8804, 'lng' => 98.3923,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 18,
            ],
            [
                'code' => 'SK01', 'province' => 'สงขลา', 'region' => 'ภาคใต้',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสงขลา (หาดใหญ่)',
                'name_en' => 'Songkhla (Hat Yai) Immigration Office',
                'address_th' => 'ถ.ปุณณกัณฑ์ ต.หาดใหญ่ อ.หาดใหญ่ จ.สงขลา 90110',
                'address_en' => 'Punnakan Rd, Hat Yai, Hat Yai, Songkhla 90110',
                'phone' => '074-257-019', 'email' => null,
                'lat' => 7.0086, 'lng' => 100.4747,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 19,
            ],
            [
                'code' => 'SR01', 'province' => 'สุราษฎร์ธานี', 'region' => 'ภาคใต้',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสุราษฎร์ธานี',
                'name_en' => 'Surat Thani Immigration Office',
                'address_th' => 'ถ.ตลาดล่าง ต.ตลาด อ.เมือง จ.สุราษฎร์ธานี 84000',
                'address_en' => 'Talat Lang Rd, Talat, Mueang, Surat Thani 84000',
                'phone' => '077-272-138', 'email' => null,
                'lat' => 9.1382, 'lng' => 99.3215,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 20,
            ],
            [
                'code' => 'KB01', 'province' => 'กระบี่', 'region' => 'ภาคใต้',
                'name_th' => 'ตรวจคนเข้าเมืองจังหวัดกระบี่',
                'name_en' => 'Krabi Immigration Office',
                'address_th' => 'ถ.อุตรกิจ ต.ปากน้ำ อ.เมือง จ.กระบี่ 81000',
                'address_en' => 'Uttarakit Rd, Pak Nam, Mueang, Krabi 81000',
                'phone' => '075-611-097', 'email' => null,
                'lat' => 8.0863, 'lng' => 98.9063,
                'opening_hours' => ['mon'=>'08:30-16:30','tue'=>'08:30-16:30','wed'=>'08:30-16:30','thu'=>'08:30-16:30','fri'=>'08:30-16:30'],
                'sort_order' => 21,
            ],

            // ── ภาคเหนือ (North) — เพิ่มเติม (Lower North) ──────────────
            ['code' => 'TAK01', 'province' => 'ตาก', 'region' => 'ภาคเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดตาก', 'name_en' => 'Tak Immigration Office', 'address_th' => 'ต.หนองหลวง อ.เมือง จ.ตาก 63000', 'address_en' => 'Nong Luang, Mueang, Tak 63000', 'phone' => '055-512-238', 'email' => null, 'lat' => 16.8839, 'lng' => 99.1258, 'opening_hours' => $hours, 'sort_order' => 22],
            ['code' => 'KPP01', 'province' => 'กำแพงเพชร', 'region' => 'ภาคเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดกำแพงเพชร', 'name_en' => 'Kamphaeng Phet Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.กำแพงเพชร 62000', 'address_en' => 'Nai Mueang, Mueang, Kamphaeng Phet 62000', 'phone' => '055-705-070', 'email' => null, 'lat' => 16.4827, 'lng' => 99.5226, 'opening_hours' => $hours, 'sort_order' => 23],
            ['code' => 'STI01', 'province' => 'สุโขทัย', 'region' => 'ภาคเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสุโขทัย', 'name_en' => 'Sukhothai Immigration Office', 'address_th' => 'ต.ธานี อ.เมือง จ.สุโขทัย 64000', 'address_en' => 'Thani, Mueang, Sukhothai 64000', 'phone' => '055-610-510', 'email' => null, 'lat' => 17.0073, 'lng' => 99.8265, 'opening_hours' => $hours, 'sort_order' => 24],
            ['code' => 'UTT01', 'province' => 'อุตรดิตถ์', 'region' => 'ภาคเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดอุตรดิตถ์', 'name_en' => 'Uttaradit Immigration Office', 'address_th' => 'ต.ท่าอิฐ อ.เมือง จ.อุตรดิตถ์ 53000', 'address_en' => 'Tha It, Mueang, Uttaradit 53000', 'phone' => '055-411-188', 'email' => null, 'lat' => 17.6200, 'lng' => 100.0993, 'opening_hours' => $hours, 'sort_order' => 25],
            ['code' => 'PNL01', 'province' => 'พิษณุโลก', 'region' => 'ภาคเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดพิษณุโลก', 'name_en' => 'Phitsanulok Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.พิษณุโลก 65000', 'address_en' => 'Nai Mueang, Mueang, Phitsanulok 65000', 'phone' => '055-258-849', 'email' => null, 'lat' => 16.8211, 'lng' => 100.2659, 'opening_hours' => $hours, 'sort_order' => 26],
            ['code' => 'PCT01', 'province' => 'พิจิตร', 'region' => 'ภาคเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดพิจิตร', 'name_en' => 'Phichit Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.พิจิตร 66000', 'address_en' => 'Nai Mueang, Mueang, Phichit 66000', 'phone' => '056-611-444', 'email' => null, 'lat' => 16.4422, 'lng' => 100.3487, 'opening_hours' => $hours, 'sort_order' => 27],
            ['code' => 'PNB01', 'province' => 'เพชรบูรณ์', 'region' => 'ภาคเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดเพชรบูรณ์', 'name_en' => 'Phetchabun Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.เพชรบูรณ์ 67000', 'address_en' => 'Nai Mueang, Mueang, Phetchabun 67000', 'phone' => '056-720-588', 'email' => null, 'lat' => 16.4189, 'lng' => 101.1591, 'opening_hours' => $hours, 'sort_order' => 28],

            // ── ภาคกลาง (Central) — เพิ่มเติม ───────────────────────────
            ['code' => 'BKK03', 'province' => 'กรุงเทพมหานคร', 'region' => 'ภาคกลาง', 'name_th' => 'ด่านตรวจคนเข้าเมืองท่าอากาศยานดอนเมือง', 'name_en' => 'Don Mueang Airport Immigration Checkpoint', 'address_th' => '222 ถ.วิภาวดีรังสิต ต.สีกัน อ.ดอนเมือง กรุงเทพมหานคร 10210', 'address_en' => '222 Vibhavadi Rangsit Rd, Si Kan, Don Mueang, Bangkok 10210', 'phone' => '02-535-1300', 'email' => null, 'lat' => 13.9125, 'lng' => 100.6067, 'opening_hours' => $hours24, 'sort_order' => 29],
            ['code' => 'BKK04', 'province' => 'กรุงเทพมหานคร', 'region' => 'ภาคกลาง', 'name_th' => 'ด่านตรวจคนเข้าเมืองท่าเรือกรุงเทพ', 'name_en' => 'Bangkok Port Immigration Checkpoint', 'address_th' => 'ท่าเรือกรุงเทพ (คลองเตย) ถ.สุนทรโกษา เขตคลองเตย กรุงเทพมหานคร 10110', 'address_en' => 'Bangkok Port (Khlong Toey), Soonthornkosa Rd, Khlong Toey, Bangkok 10110', 'phone' => '02-240-2532', 'email' => null, 'lat' => 13.6995, 'lng' => 100.5677, 'opening_hours' => $hours, 'sort_order' => 30],
            ['code' => 'AYA01', 'province' => 'พระนครศรีอยุธยา', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดพระนครศรีอยุธยา', 'name_en' => 'Ayutthaya Immigration Office', 'address_th' => 'ต.คลองสวนพลู อ.พระนครศรีอยุธยา จ.พระนครศรีอยุธยา 13000', 'address_en' => 'Khlong Suan Phlu, Phra Nakhon Si Ayutthaya, Ayutthaya 13000', 'phone' => '035-241-770', 'email' => null, 'lat' => 14.3692, 'lng' => 100.5876, 'opening_hours' => $hours, 'sort_order' => 31],
            ['code' => 'NPT01', 'province' => 'นครปฐม', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดนครปฐม', 'name_en' => 'Nakhon Pathom Immigration Office', 'address_th' => 'ต.พระปฐมเจดีย์ อ.เมือง จ.นครปฐม 73000', 'address_en' => 'Phra Pathom Chedi, Mueang, Nakhon Pathom 73000', 'phone' => '034-258-848', 'email' => null, 'lat' => 13.8199, 'lng' => 100.0628, 'opening_hours' => $hours, 'sort_order' => 32],
            ['code' => 'NY01', 'province' => 'นครนายก', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดนครนายก', 'name_en' => 'Nakhon Nayok Immigration Office', 'address_th' => 'ต.นครนายก อ.เมือง จ.นครนายก 26000', 'address_en' => 'Nakhon Nayok, Mueang, Nakhon Nayok 26000', 'phone' => '037-312-289', 'email' => null, 'lat' => 14.2069, 'lng' => 101.2130, 'opening_hours' => $hours, 'sort_order' => 33],
            ['code' => 'PKN01', 'province' => 'ประจวบคีรีขันธ์', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดประจวบคีรีขันธ์', 'name_en' => 'Prachuap Khiri Khan Immigration Office', 'address_th' => 'ต.ประจวบคีรีขันธ์ อ.เมือง จ.ประจวบคีรีขันธ์ 77000', 'address_en' => 'Prachuap Khiri Khan, Mueang, Prachuap Khiri Khan 77000', 'phone' => '032-601-365', 'email' => null, 'lat' => 11.8126, 'lng' => 99.7957, 'opening_hours' => $hours, 'sort_order' => 34],
            ['code' => 'PBI01', 'province' => 'เพชรบุรี', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดเพชรบุรี', 'name_en' => 'Phetchaburi Immigration Office', 'address_th' => 'ต.ท่าราบ อ.เมือง จ.เพชรบุรี 76000', 'address_en' => 'Tha Rap, Mueang, Phetchaburi 76000', 'phone' => '032-425-573', 'email' => null, 'lat' => 13.1119, 'lng' => 99.9398, 'opening_hours' => $hours, 'sort_order' => 35],
            ['code' => 'RBR01', 'province' => 'ราชบุรี', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดราชบุรี', 'name_en' => 'Ratchaburi Immigration Office', 'address_th' => 'ต.หน้าเมือง อ.เมือง จ.ราชบุรี 70000', 'address_en' => 'Na Mueang, Mueang, Ratchaburi 70000', 'phone' => '032-337-700', 'email' => null, 'lat' => 13.5282, 'lng' => 99.8134, 'opening_hours' => $hours, 'sort_order' => 36],
            ['code' => 'LRI01', 'province' => 'ลพบุรี', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดลพบุรี', 'name_en' => 'Lopburi Immigration Office', 'address_th' => 'ต.ทะเลชุบศร อ.เมือง จ.ลพบุรี 15000', 'address_en' => 'Thale Chup Son, Mueang, Lopburi 15000', 'phone' => '036-413-008', 'email' => null, 'lat' => 14.7995, 'lng' => 100.6534, 'opening_hours' => $hours, 'sort_order' => 37],
            ['code' => 'SBR01', 'province' => 'สระบุรี', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสระบุรี', 'name_en' => 'Saraburi Immigration Office', 'address_th' => 'ต.ปากเพรียว อ.เมือง จ.สระบุรี 18000', 'address_en' => 'Pak Phriao, Mueang, Saraburi 18000', 'phone' => '036-221-545', 'email' => null, 'lat' => 14.5289, 'lng' => 100.9106, 'opening_hours' => $hours, 'sort_order' => 38],
            ['code' => 'SBI01', 'province' => 'สิงห์บุรี', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสิงห์บุรี', 'name_en' => 'Sing Buri Immigration Office', 'address_th' => 'ต.บางพุทรา อ.เมือง จ.สิงห์บุรี 16000', 'address_en' => 'Bang Phutsa, Mueang, Sing Buri 16000', 'phone' => '036-507-129', 'email' => null, 'lat' => 14.8907, 'lng' => 100.3968, 'opening_hours' => $hours, 'sort_order' => 39],
            ['code' => 'ATG01', 'province' => 'อ่างทอง', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดอ่างทอง', 'name_en' => 'Ang Thong Immigration Office', 'address_th' => 'ต.ตลาดหลวง อ.เมือง จ.อ่างทอง 14000', 'address_en' => 'Talat Luang, Mueang, Ang Thong 14000', 'phone' => '035-611-388', 'email' => null, 'lat' => 14.5896, 'lng' => 100.4549, 'opening_hours' => $hours, 'sort_order' => 40],
            ['code' => 'SPB01', 'province' => 'สุพรรณบุรี', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสุพรรณบุรี', 'name_en' => 'Suphan Buri Immigration Office', 'address_th' => 'ต.ท่าพี่เลี้ยง อ.เมือง จ.สุพรรณบุรี 72000', 'address_en' => 'Tha Phi Liang, Mueang, Suphan Buri 72000', 'phone' => '035-525-933', 'email' => null, 'lat' => 14.4744, 'lng' => 100.1177, 'opening_hours' => $hours, 'sort_order' => 41],
            ['code' => 'NSW01', 'province' => 'นครสวรรค์', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดนครสวรรค์', 'name_en' => 'Nakhon Sawan Immigration Office', 'address_th' => 'ต.ปากน้ำโพ อ.เมือง จ.นครสวรรค์ 60000', 'address_en' => 'Pak Nam Pho, Mueang, Nakhon Sawan 60000', 'phone' => '056-221-916', 'email' => null, 'lat' => 15.7030, 'lng' => 100.1369, 'opening_hours' => $hours, 'sort_order' => 42],
            ['code' => 'UTI01', 'province' => 'อุทัยธานี', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดอุทัยธานี', 'name_en' => 'Uthai Thani Immigration Office', 'address_th' => 'ต.อุทัยใหม่ อ.เมือง จ.อุทัยธานี 61000', 'address_en' => 'Uthai Mai, Mueang, Uthai Thani 61000', 'phone' => '056-511-307', 'email' => null, 'lat' => 15.3835, 'lng' => 100.0247, 'opening_hours' => $hours, 'sort_order' => 43],
            ['code' => 'CN01', 'province' => 'ชัยนาท', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดชัยนาท', 'name_en' => 'Chai Nat Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.ชัยนาท 17000', 'address_en' => 'Nai Mueang, Mueang, Chai Nat 17000', 'phone' => '056-411-098', 'email' => null, 'lat' => 15.1851, 'lng' => 100.1251, 'opening_hours' => $hours, 'sort_order' => 44],
            ['code' => 'SPK01', 'province' => 'สมุทรปราการ', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสมุทรปราการ', 'name_en' => 'Samut Prakan Immigration Office', 'address_th' => 'ต.ปากน้ำ อ.เมือง จ.สมุทรปราการ 10270', 'address_en' => 'Pak Nam, Mueang, Samut Prakan 10270', 'phone' => '02-702-9510', 'email' => null, 'lat' => 13.5990, 'lng' => 100.5998, 'opening_hours' => $hours, 'sort_order' => 45],
            ['code' => 'SKM01', 'province' => 'สมุทรสงคราม', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสมุทรสงคราม', 'name_en' => 'Samut Songkhram Immigration Office', 'address_th' => 'ต.แม่กลอง อ.เมือง จ.สมุทรสงคราม 75000', 'address_en' => 'Mae Klong, Mueang, Samut Songkhram 75000', 'phone' => '034-711-281', 'email' => null, 'lat' => 13.4098, 'lng' => 100.0022, 'opening_hours' => $hours, 'sort_order' => 46],
            ['code' => 'SKN01', 'province' => 'สมุทรสาคร', 'region' => 'ภาคกลาง', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสมุทรสาคร', 'name_en' => 'Samut Sakhon Immigration Office', 'address_th' => 'ต.มหาชัย อ.เมือง จ.สมุทรสาคร 74000', 'address_en' => 'Maha Chai, Mueang, Samut Sakhon 74000', 'phone' => '034-411-419', 'email' => null, 'lat' => 13.5475, 'lng' => 100.2745, 'opening_hours' => $hours, 'sort_order' => 47],

            // ── ภาคตะวันออก (East) — เพิ่มเติม ───────────────────────────
            ['code' => 'JT01', 'province' => 'จันทบุรี', 'region' => 'ภาคตะวันออก', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดจันทบุรี', 'name_en' => 'Chanthaburi Immigration Office', 'address_th' => 'ต.วัดใหม่ อ.เมือง จ.จันทบุรี 22000', 'address_en' => 'Wat Mai, Mueang, Chanthaburi 22000', 'phone' => '039-302-852', 'email' => null, 'lat' => 12.6112, 'lng' => 102.1036, 'opening_hours' => $hours, 'sort_order' => 48],
            ['code' => 'CC01', 'province' => 'ฉะเชิงเทรา', 'region' => 'ภาคตะวันออก', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดฉะเชิงเทรา', 'name_en' => 'Chachoengsao Immigration Office', 'address_th' => 'ต.หน้าเมือง อ.เมือง จ.ฉะเชิงเทรา 24000', 'address_en' => 'Na Mueang, Mueang, Chachoengsao 24000', 'phone' => '038-814-228', 'email' => null, 'lat' => 13.6904, 'lng' => 101.0779, 'opening_hours' => $hours, 'sort_order' => 49],
            ['code' => 'TRT01', 'province' => 'ตราด', 'region' => 'ภาคตะวันออก', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดตราด', 'name_en' => 'Trat Immigration Office', 'address_th' => 'ต.วังกระแจะ อ.เมือง จ.ตราด 23000', 'address_en' => 'Wang Krachae, Mueang, Trat 23000', 'phone' => '039-511-080', 'email' => null, 'lat' => 12.2428, 'lng' => 102.5178, 'opening_hours' => $hours, 'sort_order' => 50],
            ['code' => 'PRI01', 'province' => 'ปราจีนบุรี', 'region' => 'ภาคตะวันออก', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดปราจีนบุรี', 'name_en' => 'Prachinburi Immigration Office', 'address_th' => 'ต.หน้าเมือง อ.เมือง จ.ปราจีนบุรี 25000', 'address_en' => 'Na Mueang, Mueang, Prachinburi 25000', 'phone' => '037-211-091', 'email' => null, 'lat' => 13.9210, 'lng' => 101.3672, 'opening_hours' => $hours, 'sort_order' => 51],
            ['code' => 'SKW01', 'province' => 'สระแก้ว', 'region' => 'ภาคตะวันออก', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสระแก้ว', 'name_en' => 'Sa Kaeo Immigration Office', 'address_th' => 'ต.สระแก้ว อ.เมือง จ.สระแก้ว 27000', 'address_en' => 'Sa Kaeo, Mueang, Sa Kaeo 27000', 'phone' => '037-425-104', 'email' => null, 'lat' => 13.8240, 'lng' => 102.0645, 'opening_hours' => $hours, 'sort_order' => 52],

            // ── ภาคตะวันออกเฉียงเหนือ (Northeast) — เพิ่มเติม ────────────
            ['code' => 'KS01', 'province' => 'กาฬสินธุ์', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดกาฬสินธุ์', 'name_en' => 'Kalasin Immigration Office', 'address_th' => 'ต.กาฬสินธุ์ อ.เมือง จ.กาฬสินธุ์ 46000', 'address_en' => 'Kalasin, Mueang, Kalasin 46000', 'phone' => '043-822-525', 'email' => null, 'lat' => 16.4314, 'lng' => 103.5059, 'opening_hours' => $hours, 'sort_order' => 53],
            ['code' => 'CP01', 'province' => 'ชัยภูมิ', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดชัยภูมิ', 'name_en' => 'Chaiyaphum Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.ชัยภูมิ 36000', 'address_en' => 'Nai Mueang, Mueang, Chaiyaphum 36000', 'phone' => '044-812-356', 'email' => null, 'lat' => 15.8068, 'lng' => 102.0317, 'opening_hours' => $hours, 'sort_order' => 54],
            ['code' => 'NP01', 'province' => 'นครพนม', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดนครพนม', 'name_en' => 'Nakhon Phanom Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.นครพนม 48000', 'address_en' => 'Nai Mueang, Mueang, Nakhon Phanom 48000', 'phone' => '042-511-235', 'email' => null, 'lat' => 17.4097, 'lng' => 104.7790, 'opening_hours' => $hours, 'sort_order' => 55],
            ['code' => 'BK01', 'province' => 'บึงกาฬ', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดบึงกาฬ', 'name_en' => 'Bueng Kan Immigration Office', 'address_th' => 'ต.บึงกาฬ อ.เมือง จ.บึงกาฬ 38000', 'address_en' => 'Bueng Kan, Mueang, Bueng Kan 38000', 'phone' => '042-491-422', 'email' => null, 'lat' => 18.3609, 'lng' => 103.6464, 'opening_hours' => $hours, 'sort_order' => 56],
            ['code' => 'BR01', 'province' => 'บุรีรัมย์', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดบุรีรัมย์', 'name_en' => 'Buriram Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.บุรีรัมย์ 31000', 'address_en' => 'Nai Mueang, Mueang, Buriram 31000', 'phone' => '044-611-380', 'email' => null, 'lat' => 14.9951, 'lng' => 103.1029, 'opening_hours' => $hours, 'sort_order' => 57],
            ['code' => 'MKM01', 'province' => 'มหาสารคาม', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดมหาสารคาม', 'name_en' => 'Maha Sarakham Immigration Office', 'address_th' => 'ต.ตลาด อ.เมือง จ.มหาสารคาม 44000', 'address_en' => 'Talat, Mueang, Maha Sarakham 44000', 'phone' => '043-711-080', 'email' => null, 'lat' => 16.1850, 'lng' => 103.3013, 'opening_hours' => $hours, 'sort_order' => 58],
            ['code' => 'MDH01', 'province' => 'มุกดาหาร', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดมุกดาหาร', 'name_en' => 'Mukdahan Immigration Office', 'address_th' => 'ต.มุกดาหาร อ.เมือง จ.มุกดาหาร 49000', 'address_en' => 'Mukdahan, Mueang, Mukdahan 49000', 'phone' => '042-611-074', 'email' => null, 'lat' => 16.5450, 'lng' => 104.7236, 'opening_hours' => $hours, 'sort_order' => 59],
            ['code' => 'YST01', 'province' => 'ยโสธร', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดยโสธร', 'name_en' => 'Yasothon Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.ยโสธร 35000', 'address_en' => 'Nai Mueang, Mueang, Yasothon 35000', 'phone' => '045-712-722', 'email' => null, 'lat' => 15.7920, 'lng' => 104.1453, 'opening_hours' => $hours, 'sort_order' => 60],
            ['code' => 'ROI01', 'province' => 'ร้อยเอ็ด', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดร้อยเอ็ด', 'name_en' => 'Roi Et Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.ร้อยเอ็ด 45000', 'address_en' => 'Nai Mueang, Mueang, Roi Et 45000', 'phone' => '043-511-291', 'email' => null, 'lat' => 16.0538, 'lng' => 103.6520, 'opening_hours' => $hours, 'sort_order' => 61],
            ['code' => 'LOE01', 'province' => 'เลย', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดเลย', 'name_en' => 'Loei Immigration Office', 'address_th' => 'ต.กุดป่อง อ.เมือง จ.เลย 42000', 'address_en' => 'Kut Pong, Mueang, Loei 42000', 'phone' => '042-812-585', 'email' => null, 'lat' => 17.4860, 'lng' => 101.7223, 'opening_hours' => $hours, 'sort_order' => 62],
            ['code' => 'SSK01', 'province' => 'ศรีสะเกษ', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดศรีสะเกษ', 'name_en' => 'Sisaket Immigration Office', 'address_th' => 'ต.เมืองเหนือ อ.เมือง จ.ศรีสะเกษ 33000', 'address_en' => 'Mueang Nuea, Mueang, Sisaket 33000', 'phone' => '045-614-160', 'email' => null, 'lat' => 15.1186, 'lng' => 104.3220, 'opening_hours' => $hours, 'sort_order' => 63],
            ['code' => 'SNK01', 'province' => 'สกลนคร', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสกลนคร', 'name_en' => 'Sakon Nakhon Immigration Office', 'address_th' => 'ต.ธาตุเชิงชุม อ.เมือง จ.สกลนคร 47000', 'address_en' => 'That Choeng Chum, Mueang, Sakon Nakhon 47000', 'phone' => '042-715-332', 'email' => null, 'lat' => 17.1545, 'lng' => 104.1438, 'opening_hours' => $hours, 'sort_order' => 64],
            ['code' => 'SRN01', 'province' => 'สุรินทร์', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสุรินทร์', 'name_en' => 'Surin Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.สุรินทร์ 32000', 'address_en' => 'Nai Mueang, Mueang, Surin 32000', 'phone' => '044-511-756', 'email' => null, 'lat' => 14.8818, 'lng' => 103.4936, 'opening_hours' => $hours, 'sort_order' => 65],
            ['code' => 'NKI01', 'province' => 'หนองคาย', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดหนองคาย', 'name_en' => 'Nong Khai Immigration Office', 'address_th' => 'ต.ในเมือง อ.เมือง จ.หนองคาย 43000', 'address_en' => 'Nai Mueang, Mueang, Nong Khai 43000', 'phone' => '042-411-633', 'email' => null, 'lat' => 17.8783, 'lng' => 102.7416, 'opening_hours' => $hours, 'sort_order' => 66],
            ['code' => 'NBP01', 'province' => 'หนองบัวลำภู', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดหนองบัวลำภู', 'name_en' => 'Nong Bua Lamphu Immigration Office', 'address_th' => 'ต.หนองบัว อ.เมือง จ.หนองบัวลำภู 39000', 'address_en' => 'Nong Bua, Mueang, Nong Bua Lamphu 39000', 'phone' => '042-378-126', 'email' => null, 'lat' => 17.2046, 'lng' => 102.4267, 'opening_hours' => $hours, 'sort_order' => 67],
            ['code' => 'ACR01', 'province' => 'อำนาจเจริญ', 'region' => 'ภาคตะวันออกเฉียงเหนือ', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดอำนาจเจริญ', 'name_en' => 'Amnat Charoen Immigration Office', 'address_th' => 'ต.บุ่ง อ.เมือง จ.อำนาจเจริญ 37000', 'address_en' => 'Bung, Mueang, Amnat Charoen 37000', 'phone' => '045-523-191', 'email' => null, 'lat' => 15.8657, 'lng' => 104.6258, 'opening_hours' => $hours, 'sort_order' => 68],

            // ── ภาคใต้ (South) — เพิ่มเติม ───────────────────────────────
            ['code' => 'PK02', 'province' => 'ภูเก็ต', 'region' => 'ภาคใต้', 'name_th' => 'ด่านตรวจคนเข้าเมืองท่าอากาศยานภูเก็ต', 'name_en' => 'Phuket Airport Immigration Checkpoint', 'address_th' => 'ท่าอากาศยานภูเก็ต ต.ไม้ขาว อ.ถลาง จ.ภูเก็ต 83110', 'address_en' => 'Phuket Airport, Mai Khao, Thalang, Phuket 83110', 'phone' => '076-327-230', 'email' => null, 'lat' => 8.1132, 'lng' => 98.3169, 'opening_hours' => $hours24, 'sort_order' => 69],
            ['code' => 'SK02', 'province' => 'สงขลา', 'region' => 'ภาคใต้', 'name_th' => 'ด่านตรวจคนเข้าเมืองท่าอากาศยานหาดใหญ่', 'name_en' => 'Hat Yai Airport Immigration Checkpoint', 'address_th' => 'ท่าอากาศยานหาดใหญ่ ต.คลองหลา อ.คลองหอยโข่ง จ.สงขลา 90115', 'address_en' => 'Hat Yai Airport, Khlong La, Khlong Hoi Khong, Songkhla 90115', 'phone' => '074-227-130', 'email' => null, 'lat' => 6.9333, 'lng' => 100.3925, 'opening_hours' => $hours24, 'sort_order' => 70],
            ['code' => 'YLA01', 'province' => 'ยะลา', 'region' => 'ภาคใต้', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดยะลา', 'name_en' => 'Yala Immigration Office', 'address_th' => 'ต.สะเตง อ.เมือง จ.ยะลา 95000', 'address_en' => 'Sateng, Mueang, Yala 95000', 'phone' => '073-212-007', 'email' => null, 'lat' => 6.5410, 'lng' => 101.2810, 'opening_hours' => $hours, 'sort_order' => 71],
            ['code' => 'YLA02', 'province' => 'ยะลา', 'region' => 'ภาคใต้', 'name_th' => 'ด่านตรวจคนเข้าเมืองเบตง', 'name_en' => 'Betong Immigration Checkpoint', 'address_th' => 'ต.เบตง อ.เบตง จ.ยะลา 95110', 'address_en' => 'Betong, Betong, Yala 95110', 'phone' => '073-230-108', 'email' => null, 'lat' => 5.7740, 'lng' => 101.0750, 'opening_hours' => $hours, 'sort_order' => 72],
            ['code' => 'NRT01', 'province' => 'นราธิวาส', 'region' => 'ภาคใต้', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดนราธิวาส', 'name_en' => 'Narathiwat Immigration Office', 'address_th' => 'ต.บางนาค อ.เมือง จ.นราธิวาส 96000', 'address_en' => 'Bang Nak, Mueang, Narathiwat 96000', 'phone' => '073-511-027', 'email' => null, 'lat' => 6.4254, 'lng' => 101.8253, 'opening_hours' => $hours, 'sort_order' => 73],
            ['code' => 'PTN01', 'province' => 'ปัตตานี', 'region' => 'ภาคใต้', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดปัตตานี', 'name_en' => 'Pattani Immigration Office', 'address_th' => 'ต.สะบารัง อ.เมือง จ.ปัตตานี 94000', 'address_en' => 'Sabarang, Mueang, Pattani 94000', 'phone' => '073-349-100', 'email' => null, 'lat' => 6.8693, 'lng' => 101.2500, 'opening_hours' => $hours, 'sort_order' => 74],
            ['code' => 'STN01', 'province' => 'สตูล', 'region' => 'ภาคใต้', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดสตูล', 'name_en' => 'Satun Immigration Office', 'address_th' => 'ต.พิมาน อ.เมือง จ.สตูล 91000', 'address_en' => 'Phiman, Mueang, Satun 91000', 'phone' => '074-711-080', 'email' => null, 'lat' => 6.6238, 'lng' => 100.0674, 'opening_hours' => $hours, 'sort_order' => 75],
            ['code' => 'TRG01', 'province' => 'ตรัง', 'region' => 'ภาคใต้', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดตรัง', 'name_en' => 'Trang Immigration Office', 'address_th' => 'ต.ทับเที่ยง อ.เมือง จ.ตรัง 92000', 'address_en' => 'Thap Thiang, Mueang, Trang 92000', 'phone' => '075-218-024', 'email' => null, 'lat' => 7.5563, 'lng' => 99.6114, 'opening_hours' => $hours, 'sort_order' => 76],
            ['code' => 'PT01', 'province' => 'พัทลุง', 'region' => 'ภาคใต้', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดพัทลุง', 'name_en' => 'Phatthalung Immigration Office', 'address_th' => 'ต.คูหาสวรรค์ อ.เมือง จ.พัทลุง 93000', 'address_en' => 'Khuha Sawan, Mueang, Phatthalung 93000', 'phone' => '074-611-080', 'email' => null, 'lat' => 7.6166, 'lng' => 100.0742, 'opening_hours' => $hours, 'sort_order' => 77],
            ['code' => 'NST01', 'province' => 'นครศรีธรรมราช', 'region' => 'ภาคใต้', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดนครศรีธรรมราช', 'name_en' => 'Nakhon Si Thammarat Immigration Office', 'address_th' => 'ต.ท่าวัง อ.เมือง จ.นครศรีธรรมราช 80000', 'address_en' => 'Tha Wang, Mueang, Nakhon Si Thammarat 80000', 'phone' => '075-356-510', 'email' => null, 'lat' => 8.4304, 'lng' => 99.9631, 'opening_hours' => $hours, 'sort_order' => 78],
            ['code' => 'CPN01', 'province' => 'ชุมพร', 'region' => 'ภาคใต้', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดชุมพร', 'name_en' => 'Chumphon Immigration Office', 'address_th' => 'ต.ท่าตะเภา อ.เมือง จ.ชุมพร 86000', 'address_en' => 'Tha Taphao, Mueang, Chumphon 86000', 'phone' => '077-503-251', 'email' => null, 'lat' => 10.4930, 'lng' => 99.1800, 'opening_hours' => $hours, 'sort_order' => 79],
            ['code' => 'RNG01', 'province' => 'ระนอง', 'region' => 'ภาคใต้', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดระนอง', 'name_en' => 'Ranong Immigration Office', 'address_th' => 'ต.เขานิเวศน์ อ.เมือง จ.ระนอง 85000', 'address_en' => 'Khao Niwet, Mueang, Ranong 85000', 'phone' => '077-823-244', 'email' => null, 'lat' => 9.9528, 'lng' => 98.6085, 'opening_hours' => $hours, 'sort_order' => 80],
            ['code' => 'PNG01', 'province' => 'พังงา', 'region' => 'ภาคใต้', 'name_th' => 'ตรวจคนเข้าเมืองจังหวัดพังงา', 'name_en' => 'Phang Nga Immigration Office', 'address_th' => 'ต.ท้ายช้าง อ.เมือง จ.พังงา 82000', 'address_en' => 'Thai Chang, Mueang, Phang Nga 82000', 'phone' => '076-412-011', 'email' => null, 'lat' => 8.4509, 'lng' => 98.5253, 'opening_hours' => $hours, 'sort_order' => 81],

            // ── ด่านตรวจคนเข้าเมืองพิเศษ — ภาคเหนือ (under บก.ตม.5) ─────
            ['code' => 'CM02', 'province' => 'เชียงใหม่', 'region' => 'ภาคเหนือ', 'name_th' => 'ด่านตรวจคนเข้าเมืองท่าอากาศยานเชียงใหม่', 'name_en' => 'Chiang Mai Airport Immigration Checkpoint', 'address_th' => 'ท่าอากาศยานเชียงใหม่ ต.สุเทพ อ.เมือง จ.เชียงใหม่ 50200', 'address_en' => 'Chiang Mai Airport, Suthep, Mueang, Chiang Mai 50200', 'phone' => '053-922-3146', 'email' => null, 'lat' => 18.7669, 'lng' => 98.9626, 'opening_hours' => $hours24, 'sort_order' => 82],
            ['code' => 'CR02', 'province' => 'เชียงราย', 'region' => 'ภาคเหนือ', 'name_th' => 'ด่านตรวจคนเข้าเมืองเชียงแสน', 'name_en' => 'Chiang Saen Immigration Checkpoint', 'address_th' => 'ต.เวียง อ.เชียงแสน จ.เชียงราย 57150', 'address_en' => 'Wiang, Chiang Saen, Chiang Rai 57150', 'phone' => '053-650-247', 'email' => null, 'lat' => 20.2738, 'lng' => 100.0856, 'opening_hours' => $hours, 'sort_order' => 83],
        ];

        // Each provincial/checkpoint office runs its own official site (verified individually
        // by domain -- Thai provincial immigration offices are not one shared portal, contrary
        // to what was assumed here previously). A handful of offices (see $noVerifiedSite below)
        // could not be confirmed to run a dedicated site of their own and fall back to the
        // Bureau's central portal instead of a guessed URL.
        $websites = [
            // ── North (บก.ตม.5) ────────────────────────────────────────
            'CM01' => 'https://chiangmai.immigration.go.th/en/front-page-english/',
            'CR01' => 'https://chiangrai.immigration.go.th/en/frontpage-english/',
            'LP01' => 'https://lampang.immigration.go.th/',
            'LN01' => 'https://lamphun.immigration.go.th/en/front-page-english/',
            'MH01' => 'https://maehongson.immigration.go.th/',
            'NAN01' => 'https://nan.immigration.go.th/',
            'PY01' => 'https://phayao.immigration.go.th/',
            'PL01' => 'https://phrae.immigration.go.th/',
            'TAK01' => 'https://tak.immigration.go.th/',
            'KPP01' => 'https://kamphaengphet.immigration.go.th/en/home-en/',
            'STI01' => 'https://sukhothai.immigration.go.th/',
            'UTT01' => 'https://uttaradit.immigration.go.th/en/',
            'PNL01' => 'https://phitsanulok.immigration.go.th/en/front-page-english/',
            'PCT01' => 'https://phichit.immigration.go.th/',
            'PNB01' => 'https://phetchabun.immigration.go.th/',
            // Airport/checkpoint services are handled directly by the parent provincial office.
            'CM02' => 'https://chiangmai.immigration.go.th/en/front-page-english/',
            'CR02' => 'https://chiangsaen.immigration.go.th/en/front-page-english/',

            // ── Central ─────────────────────────────────────────────────
            'BKK01' => 'https://bangkok.immigration.go.th/en/home_en/',
            'BKK02' => 'https://arr-bkkimmigration.go.th/',
            'KAN01' => 'https://kanchanaburi.immigration.go.th/',
            'BKK04' => 'https://bangkokport.immigration.go.th/',
            'AYA01' => 'https://ayutthaya-immigration.police.go.th/index1.html',
            'NPT01' => 'https://nakhonpathom.imm.police.go.th/',
            'PBI01' => 'https://phetchaburi.immigration.go.th/',
            'RBR01' => 'https://ratchaburi.immigration.go.th/index.php/en/',
            'LRI01' => 'https://lopburi.immigration.go.th/',
            'SBR01' => 'https://saraburi.immigration.go.th/',
            'SBI01' => 'https://singburi.immigration.go.th/',
            'SPB01' => 'https://suphanburi.immigration.go.th/',
            'NSW01' => 'https://nakhonsawan.immigration.go.th/',
            'UTI01' => 'https://uthaithani.immigration.go.th/en/',
            'SPK01' => 'https://www.samutprakanimmigration.go.th/',
            'SKM01' => 'http://www.samutsongkhramimmigration.go.th/',
            'SKN01' => 'https://www.samutsakhonimmigration.go.th/',

            // ── East ────────────────────────────────────────────────────
            'CB01' => 'https://chonburi.imm.police.go.th/',
            'RY01' => 'https://www.rayongimmigration.go.th/',
            'JT01' => 'https://chanthaburiimmigration.com/',
            'CC01' => 'https://chachoengsao.immigration.go.th/',
            'TRT01' => 'https://trat.immigration.go.th/',
            'PRI01' => 'https://prachinburi.imm.police.go.th/',
            'SKW01' => 'https://sakaeo.imm.police.go.th/',

            // ── Northeast ───────────────────────────────────────────────
            'KK01' => 'https://khonkaen.immigration.go.th/',
            'NK01' => 'https://nakhonratchasima-imm.go.th/index_e.php',
            'UD01' => 'https://udonthani.immigration.go.th/',
            'UB01' => 'https://ubon.immigration.go.th/index.php/en/',
            'KS01' => 'https://www.kalasinimmigration.go.th/',
            'CP01' => 'https://chaiyaphum.immigration.go.th/',
            'NP01' => 'https://nakhonphanom.immigration.go.th/',
            'BK01' => 'https://buengkan.immigration.go.th/',
            'BR01' => 'https://buriram.immigration.go.th/',
            'MKM01' => 'https://mahasarakham.imm.police.go.th/',
            'MDH01' => 'https://mukdahan.immigration.go.th/',
            'YST01' => 'https://yasothon.immigration.go.th/',
            'ROI01' => 'https://www.roietimmigration.com/',
            'LOE01' => 'https://loei.immigration.go.th/',
            'SSK01' => 'https://sisaket.immigration.go.th/',
            'SNK01' => 'https://sakonnakhon.immigration.go.th/',
            'SRN01' => 'https://surin.immigration.go.th/',
            'NKI01' => 'https://nongkhai.immigration.go.th/',
            'NBP01' => 'https://nongbualamphu.immigration.go.th/',
            'ACR01' => 'https://amnatcharoen.immigration.go.th/',

            // ── South (บก.ตม.6) ────────────────────────────────────────
            'PK01' => 'http://www.phuketimmigration.go.th/',
            'SK01' => 'https://songkhlaimmigration.go.th/',
            'SR01' => 'http://www.suratimmigration.go.th/',
            'KB01' => 'http://www.krabiimmigration.go.th/',
            'PK02' => 'https://phuketairportimmigration.go.th/',
            // Betong checkpoint has no site of its own; administered by Yala Immigration.
            'YLA02' => 'https://www.yalaimmigration.go.th/',
            'YLA01' => 'https://www.yalaimmigration.go.th/',
            'NRT01' => 'https://www.narathiwatimmigration.go.th/',
            'PTN01' => 'https://pattaniimmigration.go.th/frontpage',
            'STN01' => 'https://satunimmigration.go.th/',
            'TRG01' => 'https://www.trangimmigration.go.th/',
            'PT01' => 'http://www.phatthalungimmigration.go.th/index.php',
            'NST01' => 'https://nakhonsithammarat.imm.police.go.th/',
            'CPN01' => 'https://chumphon.imm.police.go.th/',
            'RNG01' => 'https://www.ranongimmigration.go.th/',
            'PNG01' => 'https://phangngaimmigration.go.th/',
        ];

        // No dedicated official site could be verified for these offices at time of writing
        // (BKK03, NY01, PKN01, ATG01, CN01, SK02) -- they fall back to the central Bureau
        // portal below rather than a guessed URL.
        $officialWebsite = 'https://www.immigration.go.th';

        foreach ($stations as $data) {
            $slug = Str::slug($data['code'] . '-' . $data['name_en']);
            $data['website'] = $websites[$data['code']] ?? $officialWebsite;

            Station::updateOrCreate(
                ['code' => $data['code']],
                array_merge($data, ['slug' => $slug, 'is_active' => true])
            );
        }

        $this->command->info('✅ Stations seeded: ' . count($stations) . ' offices');
    }
}
