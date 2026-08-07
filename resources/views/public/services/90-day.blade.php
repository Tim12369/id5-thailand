@extends('public.layouts.app')
@section('title', app()->getLocale() === 'th' ? 'คู่มือรายงานตัว 90 วัน — ขั้นตอนครบจบ' : (app()->getLocale() === 'zh' ? '90天报到完整指南' : '90-Day Report Guide — Complete Step-by-Step'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-slate-500 flex items-center gap-1.5">
        <a href="{{ route('public.home') }}" class="hover:text-indigo-700">{{ $locale === 'th' ? 'หน้าแรก' : ($locale === 'zh' ? '首页' : 'Home') }}</a>
        <span>/</span>
        <a href="{{ route('public.services.index') }}" class="hover:text-indigo-700">{{ $locale === 'th' ? 'บริการ' : ($locale === 'zh' ? '服务' : 'Services') }}</a>
        <span>/</span>
        <span>{{ $locale === 'th' ? 'รายงานตัว 90 วัน' : ($locale === 'zh' ? '90天报到' : '90-Day Report') }}</span>
    </nav>

    {{-- ── Hero ── --}}
    <div class="relative bg-linear-to-br from-indigo-900 via-indigo-800 to-indigo-700 text-white rounded-3xl p-8 overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image:url('data:image/svg+xml,<svg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'><g fill=\'none\' fill-rule=\'evenodd\'><g fill=\'%23ffffff\' fill-opacity=\'1\'><path d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/></g></g></svg>');"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="ti ti-calendar-event text-3xl text-white"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full">แบบฟอร์ม TM47</span>
                    </div>
                    <h1 class="text-3xl font-bold">
                        {{ $locale === 'th' ? 'การรายงานตัวทุก 90 วัน' : ($locale === 'zh' ? '90天定期报到' : '90-Day Stay Notification') }}
                    </h1>
                    <p class="text-indigo-200 text-sm mt-1">
                        {{ $locale === 'th' ? 'ข้อกำหนดสำหรับชาวต่างชาติที่พำนักระยะยาวในประเทศไทย' : ($locale === 'zh' ? '长期居住泰国的外国人的必要义务' : 'Required for all foreigners on long-stay visas in Thailand') }}
                    </p>
                </div>
            </div>
            <p class="text-indigo-100 leading-relaxed max-w-2xl">
                @if($locale === 'th')
                    ชาวต่างชาติที่พำนักในประเทศไทยเกิน 90 วัน ต้องรายงานตัวต่อสำนักงานตรวจคนเข้าเมือง <strong class="text-white">ทุก 90 วัน</strong> เพื่อแจ้งที่อยู่ปัจจุบัน ไม่ว่าวีซ่าจะยังมีอายุอยู่หรือไม่ก็ตาม
                @elseif($locale === 'zh')
                    在泰国居住超过90天的外国人，必须<strong class="text-white">每90天</strong>向移民局报告一次当前地址，无论签证是否仍然有效。
                @else
                    Foreigners staying in Thailand for more than 90 days must report their current address to the Immigration Bureau <strong class="text-white">every 90 days</strong>, regardless of how long their visa remains valid.
                @endif
            </p>
        </div>
    </div>

    {{-- ── Quick Stats ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([
            ['icon'=>'ti-calendar','color'=>'indigo',
             'label_th'=>'รายงานทุก','label_en'=>'Report every','label_zh'=>'每隔',
             'val_th'=>'90 วัน','val_en'=>'90 Days','val_zh'=>'90天'],
            ['icon'=>'ti-coin','color'=>'green',
             'label_th'=>'ค่าธรรมเนียม','label_en'=>'Fee','label_zh'=>'费用',
             'val_th'=>'ฟรี (ไม่เสียค่าใช้จ่าย)','val_en'=>'Free of charge','val_zh'=>'免费'],
            ['icon'=>'ti-alert-triangle','color'=>'red',
             'label_th'=>'ค่าปรับถ้าช้า','label_en'=>'Late fine','label_zh'=>'逾期罚款',
             'val_th'=>'฿2,000 บาท','val_en'=>'฿2,000','val_zh'=>'2,000泰铢'],
            ['icon'=>'ti-device-laptop','color'=>'teal',
             'label_th'=>'ทำออนไลน์ได้','label_en'=>'Online available','label_zh'=>'可在线办理',
             'val_th'=>'ได้ทุกที่ทุกเวลา','val_en'=>'Anytime, anywhere','val_zh'=>'随时随地'],
        ] as $stat)
        <div class="bg-white border border-slate-200 rounded-2xl p-4 text-center">
            <div class="w-10 h-10 bg-{{ $stat['color'] }}-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                <i class="ti {{ $stat['icon'] }} text-{{ $stat['color'] }}-600 text-xl"></i>
            </div>
            <p class="text-xs text-slate-500 font-medium mb-1">{{ $locale === 'th' ? $stat['label_th'] : ($locale === 'zh' ? $stat['label_zh'] : $stat['label_en']) }}</p>
            <p class="text-sm font-bold text-slate-800">{{ $locale === 'th' ? $stat['val_th'] : ($locale === 'zh' ? $stat['val_zh'] : $stat['val_en']) }}</p>
        </div>
        @endforeach
    </div>

    {{-- ── Jump links ── --}}
    <div class="flex flex-wrap gap-2">
        @foreach([
            '#who-must'    => ($locale === 'th' ? 'ใครต้องรายงาน?' : ($locale === 'zh' ? '谁必须报到？' : 'Who Must Report?')),
            '#how-to'      => ($locale === 'th' ? 'ขั้นตอนการรายงาน' : ($locale === 'zh' ? '报到步骤' : 'Steps to Report')),
            '#online'      => ($locale === 'th' ? 'รายงานออนไลน์' : ($locale === 'zh' ? '在线报到' : 'Report Online')),
            '#in-person'   => ($locale === 'th' ? 'รายงานที่สำนักงาน' : ($locale === 'zh' ? '亲自前往' : 'In-Person')),
            '#by-mail'     => ($locale === 'th' ? 'รายงานทางไปรษณีย์' : ($locale === 'zh' ? '邮寄报到' : 'By Mail')),
            '#penalties'   => ($locale === 'th' ? 'บทลงโทษ' : ($locale === 'zh' ? '处罚' : 'Penalties')),
            '#faq'         => ($locale === 'th' ? 'คำถามที่พบบ่อย' : ($locale === 'zh' ? '常见问题' : 'FAQ')),
        ] as $anchor => $label)
        <a href="{{ $anchor }}" class="text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200 px-3 py-1.5 rounded-full hover:bg-indigo-100 transition-colors">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- ── SECTION 1: Who Must Report ── --}}
    <section id="who-must">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">1</span>
            {{ $locale === 'th' ? 'ใครต้องรายงานตัว 90 วัน?' : ($locale === 'zh' ? '谁必须进行90天报到？' : 'Who Must File a 90-Day Report?') }}
        </h2>
        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-6">
            <p class="text-slate-700 text-sm leading-relaxed mb-5">
                @if($locale === 'th')
                    ตาม <strong>มาตรา 37(5) แห่งพระราชบัญญัติคนเข้าเมือง พ.ศ. 2522</strong> ชาวต่างชาติที่พำนักในประเทศไทยโดยได้รับอนุญาตให้อยู่ต่อเนื่องเกิน 90 วัน ต้องแจ้งที่อยู่ปัจจุบันต่อเจ้าพนักงานตรวจคนเข้าเมือง
                @elseif($locale === 'zh')
                    根据<strong>1979年《移民法》第37(5)条</strong>，获准在泰国连续居住超过90天的外国人，必须向移民官员报告其当前地址。
                @else
                    Under <strong>Section 37(5) of the Immigration Act B.E. 2522 (1979)</strong>, foreigners permitted to stay continuously in Thailand for more than 90 days must notify immigration officers of their current address.
                @endif
            </p>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach([
                    ['icon'=>'ti-circle-check','color'=>'green',
                     'th'=>'วีซ่าระยะยาว (Non-Immigrant B, ED, O, O-A ฯลฯ)','en'=>'Long-stay visa holders (Non-Imm B, ED, O, O-A, etc.)','zh'=>'长期签证持有者（非移民B、ED、O、O-A等）',
                     'desc_th'=>'รวมถึงวีซ่าทำงาน นักเรียน เกษียณ สมรส และดูแลผู้ป่วย','desc_en'=>'Includes work, student, retirement, marriage, and dependent visas','desc_zh'=>'包括工作、学生、退休、婚姻和家属签证'],
                    ['icon'=>'ti-circle-check','color'=>'green',
                     'th'=>'ได้รับการต่อวีซ่าและอยู่ต่อเนื่องเกิน 90 วัน','en'=>'Those who extended their stay beyond 90 days','zh'=>'申请延期超过90天的外国人',
                     'desc_th'=>'แม้จะต่อวีซ่าแล้ว ยังต้องรายงานตัวทุก 90 วันตลอดระยะเวลาที่อยู่','desc_en'=>'Even after extension, must continue reporting every 90 days for the entire stay','desc_zh'=>'即使延期后，在整个居住期间仍须每90天报到一次'],
                    ['icon'=>'ti-x','color'=>'red',
                     'th'=>'ไม่ต้องรายงาน: นักท่องเที่ยว (Tourist Visa / Visa Exemption)','en'=>'NOT required: Tourists (Tourist Visa / Visa Exemption)','zh'=>'不需要报到：游客（旅游签证/免签入境）',
                     'desc_th'=>'วีซ่าท่องเที่ยวส่วนใหญ่มีระยะเวลาน้อยกว่า 90 วัน จึงไม่เข้าเกณฑ์','desc_en'=>'Tourist visas are typically under 90 days and fall outside this requirement','desc_zh'=>'旅游签证通常不足90天，不在此要求范围内'],
                    ['icon'=>'ti-x','color'=>'red',
                     'th'=>'ไม่ต้องรายงาน: ผู้ถือ Thailand Elite Card (มีระบบแยกต่างหาก)','en'=>'NOT required: Thailand Elite Card holders (separate system)','zh'=>'不需要报到：泰国精英卡持有者（使用独立系统）',
                     'desc_th'=>'สมาชิก Thailand Elite มีระบบรายงานตัวเฉพาะผ่านโปรแกรมของตนเอง','desc_en'=>'Thailand Elite members have a dedicated reporting system through their program','desc_zh'=>'泰国精英会员通过其专属系统进行报到'],
                ] as $who)
                <div class="flex items-start gap-3 bg-white rounded-xl border border-indigo-200 p-4">
                    <div class="w-8 h-8 bg-{{ $who['color'] }}-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                        <i class="ti {{ $who['icon'] }} text-{{ $who['color'] }}-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ $locale === 'th' ? $who['th'] : ($locale === 'zh' ? $who['zh'] : $who['en']) }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $locale === 'th' ? $who['desc_th'] : ($locale === 'zh' ? $who['desc_zh'] : $who['desc_en']) }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── SECTION 2: Step-by-Step ── --}}
    <section id="how-to">
        <h2 class="text-xl font-bold text-slate-800 mb-2 flex items-center gap-2">
            <span class="w-8 h-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">2</span>
            {{ $locale === 'th' ? 'ขั้นตอนการรายงานตัว 90 วัน' : ($locale === 'zh' ? '90天报到步骤' : 'How to Complete Your 90-Day Report') }}
        </h2>
        <p class="text-sm text-slate-500 mb-5 ml-10">
            {{ $locale === 'th' ? 'เลือกวิธีที่สะดวกที่สุด: ออนไลน์ / ที่สำนักงาน / ทางไปรษณีย์' : ($locale === 'zh' ? '选择最方便的方式：在线/亲自前往/邮寄' : 'Choose the most convenient method: Online / In-Person / By Mail') }}
        </p>
        <div class="space-y-3">
            @foreach([
                [
                    'n'=>1,'icon'=>'ti-calendar-stats','color'=>'indigo',
                    'th'=>'รู้วันครบกำหนดของคุณ',
                    'en'=>'Know your due date',
                    'zh'=>'了解您的截止日期',
                    'detail_th'=>'นับ 90 วันนับจากวันที่คุณ (1) เข้าประเทศครั้งล่าสุด หรือ (2) รายงานตัวครั้งล่าสุด แล้วแต่อย่างหลัง ตรวจสอบตราประทับในหนังสือเดินทางหรือใบรับการรายงานตัวครั้งก่อน',
                    'detail_en'=>'Count 90 days from (1) your last entry into Thailand, or (2) your last 90-day report — whichever is more recent. Check your passport stamp or last report receipt for the date.',
                    'detail_zh'=>'从以下日期起计算90天：(1)上次入境泰国的日期，或(2)上次90天报到的日期——以较近的为准。查看护照盖章或上次报到收据确认日期。',
                ],
                [
                    'n'=>2,'icon'=>'ti-files','color'=>'blue',
                    'th'=>'เตรียมเอกสารให้พร้อม',
                    'en'=>'Prepare your documents',
                    'zh'=>'准备所需文件',
                    'detail_th'=>'เอกสารที่ต้องใช้: (1) หนังสือเดินทางตัวจริง พร้อมวีซ่าและตราประทับเข้าเมืองล่าสุด (2) แบบฟอร์ม TM47 กรอกครบถ้วน (3) ใบรับการรายงานตัวครั้งก่อน (ถ้ามี) (4) หลักฐานที่อยู่อาศัย (เช่น สัญญาเช่า หรือ ใบรับ TM30)',
                    'detail_en'=>'Required: (1) Original passport with current visa and latest entry stamp (2) Completed TM47 form (3) Previous 90-day receipt if available (4) Proof of residence (lease agreement or TM30 receipt).',
                    'detail_zh'=>'所需材料：(1)原始护照，含当前签证和最新入境章(2)填写完整的TM47表格(3)上次报到收据（如有）(4)居住证明（租赁合同或TM30收据）。',
                ],
                [
                    'n'=>3,'icon'=>'ti-route','color'=>'purple',
                    'th'=>'เลือกวิธีรายงาน',
                    'en'=>'Choose your reporting method',
                    'zh'=>'选择报到方式',
                    'detail_th'=>'มี 3 วิธี: (A) ออนไลน์ที่ tm47.immigration.go.th — สะดวก ทำได้จากบ้าน (B) ยื่นด้วยตนเองที่สำนักงานตรวจคนเข้าเมือง (C) ส่งทางไปรษณีย์ — ต้องส่งล่วงหน้า 7 วัน',
                    'detail_en'=>'3 options: (A) Online at tm47.immigration.go.th — convenient, from home (B) In-person at an Immigration Bureau office (C) By registered mail — must arrive 7 days before deadline.',
                    'detail_zh'=>'3种方式：(A)在线：tm47.immigration.go.th——方便，在家即可操作(B)亲自前往移民局办公室(C)挂号邮寄——必须在截止日期前7天寄出。',
                ],
                [
                    'n'=>4,'icon'=>'ti-send','color'=>'green',
                    'th'=>'ยื่นแบบฟอร์มและรับใบรับ',
                    'en'=>'Submit and receive your receipt',
                    'zh'=>'提交并获取收据',
                    'detail_th'=>'หลังยื่นสำเร็จ คุณจะได้รับ "ใบรับแจ้งการอยู่เกิน 90 วัน" (Acknowledgement Slip) ซึ่งระบุวันครบกำหนดครั้งต่อไป เก็บใบนี้ไว้ให้ดี',
                    'detail_en'=>'After submitting, you receive an Acknowledgement Slip with your next due date printed on it. Keep this slip — you will need it for the next report.',
                    'detail_zh'=>'提交后，您将获得一张注明下次截止日期的"收据"。请妥善保管此收据——下次报到时需要使用。',
                ],
                [
                    'n'=>5,'icon'=>'ti-calendar-plus','color'=>'orange',
                    'th'=>'จดวันครบกำหนดครั้งต่อไป',
                    'en'=>'Note your next due date',
                    'zh'=>'记录下次截止日期',
                    'detail_th'=>'วันครบกำหนดครั้งต่อไปจะพิมพ์อยู่บนใบรับ ตั้งการแจ้งเตือนล่วงหน้า 1 สัปดาห์เพื่อให้มีเวลาเตรียมเอกสาร ยื่นได้ตั้งแต่ 15 วันก่อนครบกำหนด',
                    'detail_en'=>'Your next due date is printed on the receipt. Set a reminder 1 week in advance. You can file up to 15 days before the due date.',
                    'detail_zh'=>'收据上会印有下次截止日期。提前1周设置提醒。您可以在截止日期前15天提交。',
                ],
            ] as $step)
            <div class="flex gap-4 bg-white border border-slate-200 rounded-2xl p-5 hover:border-indigo-200 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-{{ $step['color'] }}-100 flex items-center justify-center shrink-0">
                    <i class="ti {{ $step['icon'] }} text-{{ $step['color'] }}-600 text-lg"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-{{ $step['color'] }}-600 uppercase tracking-wider">{{ $locale === 'th' ? 'ขั้นตอนที่' : ($locale === 'zh' ? '步骤' : 'Step') }} {{ $step['n'] }}</span>
                    <h3 class="font-semibold text-slate-800 text-sm mb-1 mt-0.5">
                        {{ $locale === 'th' ? $step['th'] : ($locale === 'zh' ? $step['zh'] : $step['en']) }}
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        {{ $locale === 'th' ? $step['detail_th'] : ($locale === 'zh' ? $step['detail_zh'] : $step['detail_en']) }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>
    </section>

    {{-- ── SECTION 3: Three Methods ── --}}
    <section>
        <h2 class="text-xl font-bold text-slate-800 mb-5 flex items-center gap-2">
            <span class="w-8 h-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">3</span>
            {{ $locale === 'th' ? '3 วิธีรายงานตัว — เลือกที่สะดวก' : ($locale === 'zh' ? '3种报到方式——选择最方便的' : '3 Ways to Report — Choose What\'s Easiest') }}
        </h2>

        {{-- Online --}}
        <div id="online" class="bg-indigo-50 border border-indigo-200 rounded-2xl p-6 mb-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shrink-0">
                    <i class="ti ti-device-laptop text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-indigo-900">
                        {{ $locale === 'th' ? 'วิธี A — รายงานออนไลน์ (แนะนำ)' : ($locale === 'zh' ? '方式A——在线报到（推荐）' : 'Method A — Report Online (Recommended)') }}
                    </h3>
                    <p class="text-xs text-indigo-600">{{ $locale === 'th' ? 'ทำได้ตลอด 24 ชั่วโมง ไม่ต้องเดินทาง' : ($locale === 'zh' ? '全天候，无需出行' : '24/7 — no travel needed') }}</p>
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-5">
                <ol class="space-y-2">
                    @foreach([
                        ['th'=>'ไปที่ tm47.immigration.go.th','en'=>'Go to tm47.immigration.go.th','zh'=>'前往 tm47.immigration.go.th', 'link' => true],
                        ['th'=>'ลงทะเบียน หรือ เข้าสู่ระบบ (ครั้งแรกต้องสมัครสมาชิก)','en'=>'Register or log in (first-time users must create an account)','zh'=>'注册或登录（首次使用需创建账户）'],
                        ['th'=>'กรอกข้อมูลหนังสือเดินทางและที่อยู่ปัจจุบัน','en'=>'Enter your passport and current address details','zh'=>'输入护照和当前地址信息'],
                        ['th'=>'ตรวจสอบข้อมูลและยืนยันการส่ง','en'=>'Review details and confirm submission','zh'=>'检查信息并确认提交'],
                        ['th'=>'ดาวน์โหลดใบรับแจ้ง (PDF) พร้อมวันครบกำหนดครั้งต่อไป','en'=>'Download the acknowledgement slip (PDF) with your next due date','zh'=>'下载收据（PDF），上面有下次截止日期'],
                    ] as $i => $s)
                    <li class="flex items-start gap-2 text-xs text-indigo-700">
                        <span class="w-5 h-5 bg-indigo-600 text-white rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">{{ $i+1 }}</span>
                        @if($s['link'] ?? false)
                            <span>
                                @if($locale === 'th') ไปที่
                                @elseif($locale === 'zh') 前往
                                @else Go to
                                @endif
                                <a href="https://tm47.immigration.go.th/tm47/#/login" target="_blank" rel="noopener" class="font-semibold text-indigo-800 underline hover:text-indigo-900">tm47.immigration.go.th</a>
                            </span>
                        @else
                            {{ $locale === 'th' ? $s['th'] : ($locale === 'zh' ? $s['zh'] : $s['en']) }}
                        @endif
                    </li>
                    @endforeach
                </ol>
                <div class="space-y-2">
                    <p class="text-xs font-semibold text-indigo-800 mb-2">{{ $locale === 'th' ? 'ข้อดี' : ($locale === 'zh' ? '优势' : 'Benefits') }}</p>
                    @foreach([
                        ['icon'=>'ti-clock-24','th'=>'ทำได้ตลอด 24 ชั่วโมง ทุกวัน','en'=>'Available 24/7 — no office hours','zh'=>'全天候24/7'],
                        ['icon'=>'ti-car-off','th'=>'ไม่ต้องเดินทาง ไม่เสียเวลา','en'=>'No travel, no waiting in line','zh'=>'无需出行，无需排队'],
                        ['icon'=>'ti-certificate','th'=>'รับใบรับแจ้งทันที (PDF)','en'=>'Instant digital receipt','zh'=>'即时获取数字收据'],
                        ['icon'=>'ti-history','th'=>'ดูประวัติรายงานตัวย้อนหลังได้','en'=>'Access your full reporting history','zh'=>'可查看完整报到历史'],
                    ] as $b)
                    <div class="flex items-center gap-2 bg-white rounded-lg p-2.5 border border-indigo-200">
                        <i class="ti {{ $b['icon'] }} text-indigo-600 shrink-0"></i>
                        <p class="text-xs text-slate-700">{{ $locale === 'th' ? $b['th'] : ($locale === 'zh' ? $b['zh'] : $b['en']) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <a href="https://tm47.immigration.go.th/tm47/#/login" target="_blank" rel="noopener"
               class="mt-5 inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-3 rounded-xl transition-colors">
                <i class="ti ti-device-laptop text-lg"></i>
                {{ $locale === 'th' ? 'รายงานตัว 90 วันออนไลน์ทันที' : ($locale === 'zh' ? '立即在线报到' : 'File Your 90-Day Report Online Now') }}
                <i class="ti ti-external-link text-sm"></i>
            </a>
        </div>

        {{-- In-person --}}
        <div id="in-person" class="bg-white border border-slate-200 rounded-2xl p-6 mb-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shrink-0">
                    <i class="ti ti-building text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800">
                        {{ $locale === 'th' ? 'วิธี B — ยื่นด้วยตนเองที่สำนักงาน' : ($locale === 'zh' ? '方式B——亲自前往办公室' : 'Method B — In-Person at Immigration Office') }}
                    </h3>
                    <p class="text-xs text-slate-500">{{ $locale === 'th' ? 'เหมาะถ้าต้องการคำแนะนำจากเจ้าหน้าที่โดยตรง' : ($locale === 'zh' ? '适合需要直接获得工作人员指导的情况' : 'Best if you need direct assistance from officers') }}</p>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-slate-700 mb-2">{{ $locale === 'th' ? 'เอกสารที่ต้องนำไป' : ($locale === 'zh' ? '需要携带的文件' : 'Documents to Bring') }}</p>
                    <ul class="space-y-1.5">
                        @foreach([
                            ['th'=>'หนังสือเดินทางตัวจริง (ฉบับจริง พร้อมสำเนา)','en'=>'Original passport + photocopy','zh'=>'原始护照 + 复印件'],
                            ['th'=>'แบบฟอร์ม TM47 กรอกครบถ้วน (รับได้ที่สำนักงาน)','en'=>'Completed TM47 form (available at the office)','zh'=>'填写完整的TM47表格（可在办公室领取）'],
                            ['th'=>'ใบรับการรายงานตัวครั้งก่อน (ถ้ามี)','en'=>'Previous acknowledgement slip (if available)','zh'=>'上次报到收据（如有）'],
                            ['th'=>'หลักฐานที่อยู่ เช่น สัญญาเช่า หรือ ใบรับ TM30','en'=>'Proof of address (lease or TM30 receipt)','zh'=>'地址证明（租约或TM30收据）'],
                        ] as $doc)
                        <li class="flex items-start gap-2 text-xs text-slate-600">
                            <i class="ti ti-circle-check text-blue-500 shrink-0 mt-0.5"></i>
                            {{ $locale === 'th' ? $doc['th'] : ($locale === 'zh' ? $doc['zh'] : $doc['en']) }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-700 mb-2">{{ $locale === 'th' ? 'ขั้นตอนที่สำนักงาน' : ($locale === 'zh' ? '办公室办理步骤' : 'Steps at the Office') }}</p>
                    <ol class="space-y-1.5">
                        @foreach([
                            ['th'=>'นำบัตรคิวจากเครื่องหมายเลข','en'=>'Take a queue number from the machine','zh'=>'从机器取号排队'],
                            ['th'=>'รอเรียกชื่อ ยื่นเอกสารทั้งหมด','en'=>'Wait to be called, submit all documents','zh'=>'等候叫号，提交所有文件'],
                            ['th'=>'เจ้าหน้าที่ตรวจสอบและประทับตรา','en'=>'Officer verifies and stamps your passport','zh'=>'工作人员核查并在护照上盖章'],
                            ['th'=>'รับใบรับแจ้งพร้อมวันครบกำหนดครั้งต่อไป','en'=>'Receive your receipt with the next due date','zh'=>'获取收据及下次截止日期'],
                        ] as $i => $s)
                        <li class="flex items-start gap-2 text-xs text-slate-600">
                            <span class="w-4 h-4 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">{{ $i+1 }}</span>
                            {{ $locale === 'th' ? $s['th'] : ($locale === 'zh' ? $s['zh'] : $s['en']) }}
                        </li>
                        @endforeach
                    </ol>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-2.5 mt-3">
                        <p class="text-xs text-amber-700">
                            <i class="ti ti-clock mr-1"></i>
                            {{ $locale === 'th' ? 'ไปเช้า — หลีกเลี่ยงเวลาพักเที่ยง (12.00–13.00 น.) เวลาทำการ: จ–ศ 08.30–16.30 น.' : ($locale === 'zh' ? '早点到——避开午休时间（12:00-13:00）。办公时间：周一至周五 08:30-16:30' : 'Go early — avoid the lunch break (12:00–13:00). Hours: Mon–Fri 08:30–16:30.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- By Mail --}}
        <div id="by-mail" class="bg-white border border-slate-200 rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-teal-600 rounded-xl flex items-center justify-center shrink-0">
                    <i class="ti ti-mail text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800">
                        {{ $locale === 'th' ? 'วิธี C — ส่งทางไปรษณีย์ลงทะเบียน' : ($locale === 'zh' ? '方式C——挂号邮寄' : 'Method C — Registered Mail') }}
                    </h3>
                    <p class="text-xs text-slate-500">{{ $locale === 'th' ? 'ต้องส่งล่วงหน้าอย่างน้อย 7 วันก่อนครบกำหนด' : ($locale === 'zh' ? '必须在截止日期前至少7天寄出' : 'Must be sent at least 7 days before your deadline') }}</p>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-slate-700 mb-2">{{ $locale === 'th' ? 'เอกสารที่ต้องส่ง' : ($locale === 'zh' ? '需要邮寄的文件' : 'Documents to Send') }}</p>
                    <ul class="space-y-1.5">
                        @foreach([
                            ['th'=>'แบบฟอร์ม TM47 กรอกครบถ้วน','en'=>'Completed TM47 form','zh'=>'填写完整的TM47表格'],
                            ['th'=>'สำเนาหนังสือเดินทาง (หน้าวีซ่า + หน้าตราประทับล่าสุด)','en'=>'Passport copy (visa page + latest entry stamp page)','zh'=>'护照复印件（签证页+最新入境章页）'],
                            ['th'=>'สำเนาใบรับการรายงานตัวครั้งก่อน','en'=>'Copy of previous acknowledgement slip','zh'=>'上次报到收据复印件'],
                            ['th'=>'ซองส่งคืนพร้อมติดแสตมป์ (ชื่อ + ที่อยู่ของคุณ)','en'=>'Self-addressed stamped envelope for the reply','zh'=>'带邮票的回寄信封（写上您的姓名和地址）'],
                        ] as $doc)
                        <li class="flex items-start gap-2 text-xs text-slate-600">
                            <i class="ti ti-circle-check text-teal-500 shrink-0 mt-0.5"></i>
                            {{ $locale === 'th' ? $doc['th'] : ($locale === 'zh' ? $doc['zh'] : $doc['en']) }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-xs font-semibold text-amber-800 mb-2">
                        <i class="ti ti-alert-triangle mr-1"></i>
                        {{ $locale === 'th' ? 'ข้อควรระวัง — ทางไปรษณีย์' : ($locale === 'zh' ? '注意事项——邮寄方式' : 'Important Cautions — Mail Method') }}
                    </p>
                    <ul class="space-y-1.5 text-xs text-amber-700">
                        <li>{{ $locale === 'th' ? '• ส่งไปรษณีย์ลงทะเบียน ไม่ใช่ไปรษณีย์ธรรมดา' : ($locale === 'zh' ? '• 使用挂号邮件，而非普通邮件' : '• Use registered mail, not regular post') }}</li>
                        <li>{{ $locale === 'th' ? '• ส่งก่อนครบกำหนดอย่างน้อย 7 วัน เผื่อเวลาส่ง' : ($locale === 'zh' ? '• 至少提前7天寄出，预留邮递时间' : '• Send at least 7 days early to allow delivery time') }}</li>
                        <li>{{ $locale === 'th' ? '• เก็บหลักฐานการส่ง (tracking number) ไว้เสมอ' : ($locale === 'zh' ? '• 始终保留邮寄证明（快递单号）' : '• Keep your proof of postage / tracking number') }}</li>
                        <li>{{ $locale === 'th' ? '• วิธีนี้ไม่แนะนำสำหรับมือใหม่ — ใช้ออนไลน์หรือไปสำนักงานดีกว่า' : ($locale === 'zh' ? '• 不建议新手使用此方式——建议选择在线或亲自前往' : '• Not recommended for first-timers — use online or in-person instead') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ── SECTION 4: Timeline / Deadline Rules ── --}}
    <section>
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">4</span>
            {{ $locale === 'th' ? 'กำหนดเวลาและกฎที่สำคัญ' : ($locale === 'zh' ? '重要截止时间和规则' : 'Key Deadline Rules') }}
        </h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach([
                ['icon'=>'ti-clock','color'=>'indigo','border'=>'border-indigo-200',
                 'title_th'=>'ยื่นก่อนครบกำหนดได้ 15 วัน','title_en'=>'File up to 15 days early','title_zh'=>'可提前15天提交',
                 'desc_th'=>'คุณสามารถยื่นรายงานตัวได้ตั้งแต่ 15 วันก่อนวันครบกำหนด ไม่ต้องรอให้ถึงวันสุดท้าย',
                 'desc_en'=>'You can file your 90-day report up to 15 days before it is due. Don\'t wait until the last day.',
                 'desc_zh'=>'您可以在截止日期前15天提交90天报到。不要等到最后一天。'],
                ['icon'=>'ti-plane-departure','color'=>'blue','border'=>'border-blue-200',
                 'title_th'=>'ออกนอกประเทศ = รีเซ็ตนับใหม่','title_en'=>'Leaving resets the 90-day count','title_zh'=>'出境重置90天计数',
                 'desc_th'=>'ถ้าคุณออกนอกประเทศและกลับเข้ามา นับ 90 วันเริ่มใหม่จากวันที่เข้าประเทศล่าสุด ไม่ใช่จากวันรายงานตัวครั้งก่อน',
                 'desc_en'=>'If you leave and re-enter Thailand, your 90-day count resets from the new entry date — not from your previous report date.',
                 'desc_zh'=>'如果您离开泰国后再次入境，90天计数从新的入境日期重新开始——而非从上次报到日期算起。'],
                ['icon'=>'ti-calendar-x','color'=>'red','border'=>'border-red-200',
                 'title_th'=>'ยื่นหลังครบกำหนด = ค่าปรับ','title_en'=>'Filing late = fine','title_zh'=>'逾期提交 = 罚款',
                 'desc_th'=>'ถ้าพลาดกำหนดและยื่นช้า จะถูกปรับสูงสุด ฿2,000 บาท ควรยื่นทันทีแม้จะเลยกำหนดแล้ว ดีกว่าไม่ยื่นเลย',
                 'desc_en'=>'Missing the deadline and filing late incurs a fine of up to ฿2,000. Still file immediately even if late — it is better than not filing at all.',
                 'desc_zh'=>'错过截止日期并逾期提交将被罚款最高2,000泰铢。即使逾期也应立即提交——总比完全不提交要好。'],
                ['icon'=>'ti-refresh','color'=>'teal','border'=>'border-teal-200',
                 'title_th'=>'วีซ่าต่อ ≠ ยกเว้นการรายงาน','title_en'=>'Visa extension ≠ reporting exemption','title_zh'=>'签证续期 ≠ 豁免报到',
                 'desc_th'=>'การต่อวีซ่าไม่ได้รีเซ็ตวันรายงาน 90 วัน คุณยังต้องรายงานตัวแยกต่างหากตามกำหนดเดิม',
                 'desc_en'=>'Extending your visa does NOT reset your 90-day report date. You must still report separately on your existing schedule.',
                 'desc_zh'=>'续签签证不会重置您的90天报到日期。您仍须按原有时间表单独报到。'],
                ['icon'=>'ti-file-check','color'=>'green','border'=>'border-green-200',
                 'title_th'=>'ยื่นขอต่อวีซ่า = ยื่นทั้งสองอย่าง','title_en'=>'Filing visa extension = file both simultaneously','title_zh'=>'申请签证续期 = 同时提交两项',
                 'desc_th'=>'เมื่อยื่นต่อวีซ่า ถ้าใกล้ครบ 90 วัน แจ้งเจ้าหน้าที่เพื่อยื่นรายงาน 90 วันพร้อมกันเลย ประหยัดเวลา',
                 'desc_en'=>'When filing a visa extension, if your 90-day report is due soon, tell the officer and file both at the same time — saves a second trip.',
                 'desc_zh'=>'办理签证续期时，如果90天报到即将到期，告知工作人员同时办理——节省一次出行。'],
                ['icon'=>'ti-passport','color'=>'orange','border'=>'border-orange-200',
                 'title_th'=>'เปลี่ยนหนังสือเดินทาง — ต้องแจ้งด้วย','title_en'=>'New passport — notify immigration too','title_zh'=>'换发护照——也需通知移民局',
                 'desc_th'=>'ถ้าต่ออายุหนังสือเดินทางเล่มใหม่ ต้องนำทั้งเล่มใหม่และเล่มเก่าไปแจ้งที่สำนักงานตรวจคนเข้าเมือง เพื่ออัปเดตบันทึกด้วย',
                 'desc_en'=>'If you get a new passport, bring both old and new passports to immigration to update your records before filing.',
                 'desc_zh'=>'如果您更换了护照，请携带新旧护照前往移民局更新记录后再进行报到。'],
            ] as $rule)
            <div class="bg-white border {{ $rule['border'] }} rounded-2xl p-4">
                <div class="w-9 h-9 bg-{{ $rule['color'] }}-100 rounded-xl flex items-center justify-center mb-3">
                    <i class="ti {{ $rule['icon'] }} text-{{ $rule['color'] }}-600"></i>
                </div>
                <h3 class="font-bold text-slate-800 text-sm mb-1.5">{{ $locale === 'th' ? $rule['title_th'] : ($locale === 'zh' ? $rule['title_zh'] : $rule['title_en']) }}</h3>
                <p class="text-xs text-slate-500 leading-relaxed">{{ $locale === 'th' ? $rule['desc_th'] : ($locale === 'zh' ? $rule['desc_zh'] : $rule['desc_en']) }}</p>
            </div>
            @endforeach
        </div>
    </section>

    {{-- ── SECTION 5: Penalties ── --}}
    <section id="penalties">
        <div class="bg-red-50 border-2 border-red-300 rounded-2xl p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center shrink-0">
                    <i class="ti ti-gavel text-red-600 text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-bold text-red-800 mb-3">
                        {{ $locale === 'th' ? '⚠️ บทลงโทษสำหรับผู้ไม่รายงานตัว' : ($locale === 'zh' ? '⚠️ 不报到的处罚' : '⚠️ Penalties for Not Reporting') }}
                    </h2>
                    <div class="grid sm:grid-cols-2 gap-3 mb-4">
                        @foreach([
                            ['icon'=>'ti-coin','color'=>'red',
                             'th'=>'ค่าปรับสูงสุด ฿2,000 บาท','en'=>'Fine up to ฿2,000','zh'=>'罚款最高2,000泰铢',
                             'desc_th'=>'สำหรับการรายงานตัวล่าช้าหรือไม่ได้รายงานเลย','desc_en'=>'For late or missed 90-day reports','desc_zh'=>'针对逾期或未报到的情况'],
                            ['icon'=>'ti-ban','color'=>'orange',
                             'th'=>'อาจถูกปฏิเสธการต่อวีซ่า','en'=>'Visa extension may be refused','zh'=>'签证续期可能被拒',
                             'desc_th'=>'เจ้าหน้าที่มีสิทธิ์ปฏิเสธคำขอต่อวีซ่าหากพบว่าไม่ได้รายงานตัว','desc_en'=>'Officers may refuse your extension application if no 90-day report is on file','desc_zh'=>'如果记录中没有90天报到，工作人员可能拒绝您的续期申请'],
                            ['icon'=>'ti-alert-circle','color'=>'red',
                             'th'=>'อาจถูกดำเนินคดีตามกฎหมาย','en'=>'Possible legal proceedings','zh'=>'可能面临法律诉讼',
                             'desc_th'=>'การละเมิดซ้ำหรือเจตนาไม่รายงานอาจนำไปสู่การดำเนินคดี','desc_en'=>'Repeated violations or intentional non-compliance may lead to prosecution','desc_zh'=>'屡次违规或故意不合规可能导致起诉'],
                            ['icon'=>'ti-plane-off','color'=>'purple',
                             'th'=>'อาจส่งผลต่อสถานะวีซ่าในอนาคต','en'=>'May affect future visa applications','zh'=>'可能影响未来签证申请',
                             'desc_th'=>'ประวัติการไม่รายงานตัวอาจถูกบันทึกและส่งผลต่อการขอวีซ่าครั้งต่อไป','desc_en'=>'A history of non-compliance may be recorded and affect future visa requests','desc_zh'=>'不合规历史记录可能被存档，影响未来签证申请'],
                        ] as $p)
                        <div class="flex items-start gap-3 bg-white rounded-xl p-3 border border-red-200">
                            <i class="ti {{ $p['icon'] }} text-{{ $p['color'] }}-600 text-lg shrink-0 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $locale === 'th' ? $p['th'] : ($locale === 'zh' ? $p['zh'] : $p['en']) }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $locale === 'th' ? $p['desc_th'] : ($locale === 'zh' ? $p['desc_zh'] : $p['desc_en']) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="bg-red-100 rounded-xl p-3 text-sm text-red-800 font-medium">
                        <i class="ti ti-info-circle mr-2"></i>
                        {{ $locale === 'th' ? 'แนะนำ: ตั้งปฏิทินแจ้งเตือนทุก 75 วันหลังรายงานตัวแต่ละครั้ง เพื่อให้มีเวลาเตรียมตัว' : ($locale === 'zh' ? '建议：每次报到后设置75天的日历提醒，为下次报到留出准备时间。' : 'Tip: Set a calendar reminder for 75 days after each report — gives you 15 days buffer to prepare.') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── SECTION 6: FAQ ── --}}
    <section id="faq">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">6</span>
            {{ $locale === 'th' ? 'คำถามที่พบบ่อย' : ($locale === 'zh' ? '常见问题' : 'Frequently Asked Questions') }}
        </h2>
        <div class="space-y-3" x-data="{open: null}">
            @foreach([
                [
                    'q_th'=>'ฉันอยู่ไทยมา 2 เดือน ต้องรายงานตัวไหม?',
                    'q_en'=>'I\'ve been in Thailand for 2 months — do I need to file?',
                    'q_zh'=>'我在泰国已经2个月了——需要报到吗？',
                    'a_th'=>'ถ้าวีซ่าของคุณมีระยะพำนักเกิน 90 วัน (เช่น Non-Immigrant ต่างๆ หรือ Extension of Stay) คุณต้องรายงานตัว แต่ถ้าวีซ่าท่องเที่ยวและออกก่อนครบ 90 วัน ไม่ต้องรายงาน',
                    'a_en'=>'If your visa or permission to stay exceeds 90 days (e.g., Non-Immigrant, Extension of Stay), yes, you must file. If you\'re on a tourist visa and leaving before 90 days, no reporting is required.',
                    'a_zh'=>'如果您的签证或居留许可超过90天（如非移民类签证、延期居留），则需要报到。如果您持旅游签证且在90天内离境，则无需报到。',
                ],
                [
                    'q_th'=>'ฉันออกนอกประเทศแล้วกลับมา ต้องเริ่มนับ 90 วันใหม่ไหม?',
                    'q_en'=>'I left Thailand and came back — does the 90-day count restart?',
                    'q_zh'=>'我离开泰国后回来了——90天计数会重新开始吗？',
                    'a_th'=>'ใช่ ทุกครั้งที่เข้าประเทศใหม่ จะเริ่มนับ 90 วันใหม่จากวันที่เข้าประเทศ ไม่ใช่จากวันที่รายงานตัวครั้งก่อน',
                    'a_en'=>'Yes. Every time you re-enter Thailand, the 90-day count starts fresh from your new entry date — not from your previous report date.',
                    'a_zh'=>'是的。每次重新入境泰国，90天计数从新的入境日期重新开始——而非从上次报到日期算起。',
                ],
                [
                    'q_th'=>'ถ้าลืมรายงานตัวและเลยกำหนดไปแล้ว ต้องทำอะไร?',
                    'q_en'=>'I missed my 90-day deadline — what should I do now?',
                    'q_zh'=>'我错过了90天截止日期——现在该怎么办？',
                    'a_th'=>'ให้รีบไปรายงานตัวที่สำนักงานตรวจคนเข้าเมืองโดยเร็วที่สุด จะมีค่าปรับ ฿2,000 บาท แต่ดีกว่าไม่ไปเลย และไม่ควรพยายามรายงานออนไลน์ถ้าเลยกำหนดแล้ว — ไปที่สำนักงานโดยตรงดีกว่า',
                    'a_en'=>'Go to the Immigration Bureau in person as soon as possible. You will pay a ฿2,000 fine, but this is far better than continued non-compliance. If you are overdue, go in person rather than online.',
                    'a_zh'=>'尽快亲自前往移民局。您需要支付2,000泰铢的罚款，但这比继续不合规要好得多。如果已逾期，请亲自前往而非选择在线报到。',
                ],
                [
                    'q_th'=>'วีซ่าฉันจะหมดใน 2 สัปดาห์ แต่ครบ 90 วันพอดี ต้องรายงานไหม?',
                    'q_en'=>'My visa expires in 2 weeks but my 90-day report is due — do I still need to file?',
                    'q_zh'=>'我的签证将在2周内到期，但90天报到也到期了——还需要报到吗？',
                    'a_th'=>'ถ้าคุณกำลังจะต่อวีซ่าอยู่แล้ว ให้แจ้งเจ้าหน้าที่ว่าต้องการยื่น 90 วันพร้อมกันกับการต่อวีซ่า แต่ถ้าวีซ่าหมดและจะออกจากประเทศ ก็ไม่จำเป็นต้องรายงาน',
                    'a_en'=>'If you are extending your visa, tell the officer you want to file your 90-day report at the same time. If your visa is expiring and you are leaving Thailand, no further report is needed.',
                    'a_zh'=>'如果您正在续签签证，告知工作人员您想同时办理90天报到。如果签证到期且您将离开泰国，则无需再进行报到。',
                ],
                [
                    'q_th'=>'สามารถรายงานตัวแทนบุคคลอื่นได้ไหม?',
                    'q_en'=>'Can someone file the 90-day report on my behalf?',
                    'q_zh'=>'可以由他人代为提交90天报到吗？',
                    'a_th'=>'ได้ โดยผู้แทนต้องนำหนังสือมอบอำนาจ, สำเนาบัตรประชาชนของตัวเอง, และเอกสารของผู้ยื่นครบชุด ไปยื่นที่สำนักงาน สำหรับออนไลน์ ผู้ถือบัญชีสามารถยื่นเองได้',
                    'a_en'=>'Yes. The representative must bring a letter of authorization, their own ID copy, and the full set of the applicant\'s documents to file in person. For online filing, the account holder files themselves.',
                    'a_zh'=>'可以。代理人必须携带授权书、本人身份证复印件以及申请人的完整文件亲自前往办理。在线报到须由账户持有人本人操作。',
                ],
            ] as $i => $faq)
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                <button
                    @click="open = open === {{ $i }} ? null : {{ $i }}"
                    class="w-full text-left px-5 py-4 flex items-center justify-between gap-3 hover:bg-slate-50 transition-colors"
                >
                    <span class="font-medium text-slate-800 text-sm">{{ $locale === 'th' ? $faq['q_th'] : ($locale === 'zh' ? $faq['q_zh'] : $faq['q_en']) }}</span>
                    <i class="ti ti-chevron-down text-slate-400 shrink-0 transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open === {{ $i }}" x-transition class="px-5 pb-4 text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                    {{ $locale === 'th' ? $faq['a_th'] : ($locale === 'zh' ? $faq['a_zh'] : $faq['a_en']) }}
                </div>
            </div>
            @endforeach
        </div>
    </section>

    {{-- ── Downloadable Forms ── --}}
    @if($forms->count())
    <section>
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="ti ti-download text-indigo-600"></i>
            {{ $locale === 'th' ? 'ดาวน์โหลดแบบฟอร์ม' : ($locale === 'zh' ? '下载表格' : 'Download Forms') }}
        </h2>
        <div class="space-y-2">
            @foreach($forms as $form)
            <div class="flex items-center gap-4 p-3 bg-white rounded-xl border border-slate-200">
                <i class="ti ti-file-type-pdf text-red-600 text-xl shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800">{{ $form->getLocalizedName() }}</p>
                    <p class="text-xs text-slate-400">{{ $form->code }} · v{{ $form->version }}</p>
                </div>
                <a href="{{ route('public.downloads.download', $form->id) }}"
                   class="text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors" download>
                    <i class="ti ti-download mr-1"></i>PDF
                </a>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── Help CTAs ── --}}
    <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-indigo-600 rounded-2xl p-5 text-white flex items-center gap-4">
            <i class="ti ti-robot text-4xl opacity-80 shrink-0"></i>
            <div>
                <p class="font-bold mb-1">{{ $locale === 'th' ? 'ถามผู้ช่วย AI' : ($locale === 'zh' ? '询问AI助手' : 'Ask the AI Assistant') }}</p>
                <p class="text-indigo-100 text-xs mb-3">{{ $locale === 'th' ? 'มีคำถามเกี่ยวกับ 90 วัน? คลิกปุ่ม AI ที่มุมขวาล่าง' : ($locale === 'zh' ? '关于90天报到有疑问？点击右下角AI按钮' : 'Questions about the 90-day report? Click the AI button at the bottom right') }}</p>
                <span class="text-xs bg-white/20 rounded-lg px-3 py-1">{{ $locale === 'th' ? 'พร้อมให้บริการ 24/7' : ($locale === 'zh' ? '24/7全天候服务' : 'Available 24/7') }}</span>
            </div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-center gap-4">
            <i class="ti ti-phone text-green-600 text-4xl shrink-0"></i>
            <div>
                <p class="font-bold text-slate-800 mb-1">{{ $locale === 'th' ? 'สายด่วนตรวจคนเข้าเมือง' : ($locale === 'zh' ? '移民局热线' : 'Immigration Hotline') }}</p>
                <p class="text-3xl font-bold text-green-600 mb-1">1178</p>
                <p class="text-xs text-slate-500">{{ $locale === 'th' ? 'ตลอด 24 ชั่วโมง' : ($locale === 'zh' ? '24小时服务' : '24 hours a day') }}</p>
            </div>
        </div>
    </div>

</div>
@endsection
