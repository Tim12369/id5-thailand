@extends('public.layouts.app')
@section('title', app()->getLocale() === 'th' ? 'คู่มือแจ้ง TM30 — ขั้นตอนครบจบ' : (app()->getLocale() === 'zh' ? 'TM30通知完整指南' : 'TM30 Notification Guide — Complete Step-by-Step'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-slate-500 flex items-center gap-1.5">
        <a href="{{ route('public.home') }}" class="hover:text-teal-700">{{ $locale === 'th' ? 'หน้าแรก' : ($locale === 'zh' ? '首页' : 'Home') }}</a>
        <span>/</span>
        <a href="{{ route('public.services.index') }}" class="hover:text-teal-700">{{ $locale === 'th' ? 'บริการ' : ($locale === 'zh' ? '服务' : 'Services') }}</a>
        <span>/</span>
        <span>TM30</span>
    </nav>

    {{-- ── Hero ── --}}
    <div class="relative bg-linear-to-br from-teal-900 via-teal-800 to-teal-700 text-white rounded-3xl p-8 overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image:url('data:image/svg+xml,<svg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'><g fill=\'none\' fill-rule=\'evenodd\'><g fill=\'%23ffffff\' fill-opacity=\'1\'><path d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/></g></g></svg>');"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="ti ti-file-text text-3xl text-white"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full">แบบฟอร์ม TM30</span>
                    </div>
                    <h1 class="text-3xl font-bold">
                        {{ $locale === 'th' ? 'การแจ้งที่พักอาศัยคนต่างด้าว' : ($locale === 'zh' ? 'TM30外国人住宿通知' : 'TM30 Foreigner Accommodation Notification') }}
                    </h1>
                    <p class="text-teal-200 text-sm mt-1">
                        {{ $locale === 'th' ? 'ภาระหน้าที่ตามกฎหมายสำหรับเจ้าของที่พัก โรงแรม และนายจ้าง' : ($locale === 'zh' ? '住宿提供者、酒店和雇主的法律义务' : 'Legal obligation for accommodation providers, hotels and employers') }}
                    </p>
                </div>
            </div>
            <p class="text-teal-100 leading-relaxed max-w-2xl">
                @if($locale === 'th')
                    กฎหมายไทยกำหนดให้เจ้าของที่พัก โรงแรม บ้านเช่า หรือนายจ้างที่มีชาวต่างชาติพักอาศัย ต้องแจ้งที่พักต่อสำนักงานตรวจคนเข้าเมือง <strong class="text-white">ภายใน 24 ชั่วโมง</strong> นับตั้งแต่ชาวต่างชาติเข้าพัก
                @elseif($locale === 'zh')
                    泰国法律规定，提供住宿给外国人的房东、酒店、出租屋或雇主，必须在外国人入住后<strong class="text-white">24小时内</strong>向移民局报告。
                @else
                    Thai law requires any accommodation provider — hotel, rental house, or employer — housing a foreigner to notify the Immigration Bureau <strong class="text-white">within 24 hours</strong> of the foreigner's arrival.
                @endif
            </p>
        </div>
    </div>

    {{-- ── Critical Alert ── --}}
    <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-5 flex items-start gap-4">
        <i class="ti ti-alert-triangle text-amber-500 text-3xl shrink-0 mt-0.5"></i>
        <div>
            <h2 class="font-bold text-amber-900 mb-1">
                {{ $locale === 'th' ? '⚠️ สำคัญมาก — ใครต้องแจ้ง TM30?' : ($locale === 'zh' ? '⚠️ 重要 — 谁必须提交TM30？' : '⚠️ Important — Who Must File TM30?') }}
            </h2>
            <div class="grid sm:grid-cols-2 gap-3 mt-3">
                @foreach([
                    ['icon'=>'ti-building','color'=>'teal',
                     'th'=>'โรงแรม / เกสต์เฮ้าส์ / หอพัก','en'=>'Hotels / Guesthouses / Hostels','zh'=>'酒店/民宿/宿舍',
                     'desc_th'=>'ทุกครั้งที่รับแขกชาวต่างชาติเข้าพัก','desc_en'=>'Every time a foreign guest checks in','desc_zh'=>'每次外国客人入住时'],
                    ['icon'=>'ti-home','color'=>'blue',
                     'th'=>'เจ้าของบ้าน / ผู้ให้เช่า','en'=>'House owners / Landlords','zh'=>'房主/房东',
                     'desc_th'=>'เมื่อให้ชาวต่างชาติเช่าหรือพักอาศัย','desc_en'=>'When renting to or housing a foreigner','desc_zh'=>'当向外国人出租或提供住宿时'],
                    ['icon'=>'ti-briefcase','color'=>'purple',
                     'th'=>'นายจ้าง / บริษัท','en'=>'Employers / Companies','zh'=>'雇主/公司',
                     'desc_th'=>'เมื่อพนักงานชาวต่างชาติพักในที่พักของบริษัท','desc_en'=>'When foreign employees stay in company accommodation','desc_zh'=>'当外国员工住在公司提供的住所时'],
                    ['icon'=>'ti-users','color'=>'orange',
                     'th'=>'เจ้าบ้าน / เจ้าของที่ให้ญาติต่างชาติพัก','en'=>'Homeowners hosting foreign family/guests','zh'=>'接待外国亲友的房主',
                     'desc_th'=>'แม้แต่คนไทยที่ให้คู่สมรสหรือญาติชาวต่างชาติพักอาศัย','desc_en'=>'Even Thai nationals housing a foreign spouse or relative','desc_zh'=>'即使是接待外国配偶或亲属的泰国公民'],
                ] as $who)
                <div class="flex items-start gap-3 bg-white rounded-xl border border-amber-200 p-3">
                    <div class="w-9 h-9 bg-{{ $who['color'] }}-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="ti {{ $who['icon'] }} text-{{ $who['color'] }}-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ $locale === 'th' ? $who['th'] : ($locale === 'zh' ? $who['zh'] : $who['en']) }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $locale === 'th' ? $who['desc_th'] : ($locale === 'zh' ? $who['desc_zh'] : $who['desc_en']) }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Jump links ── --}}
    <div class="flex flex-wrap gap-2">
        @foreach([
            '#what-is-tm30' => ($locale === 'th' ? 'TM30 คืออะไร?' : ($locale === 'zh' ? '什么是TM30？' : 'What is TM30?')),
            '#how-to-file'  => ($locale === 'th' ? 'วิธีแจ้ง' : ($locale === 'zh' ? '如何申报' : 'How to File')),
            '#online'       => ($locale === 'th' ? 'แจ้งออนไลน์' : ($locale === 'zh' ? '在线申报' : 'File Online')),
            '#deadline'     => ($locale === 'th' ? 'กำหนดเวลา' : ($locale === 'zh' ? '截止时间' : 'Deadline')),
            '#penalties'    => ($locale === 'th' ? 'บทลงโทษ' : ($locale === 'zh' ? '处罚' : 'Penalties')),
            '#faq'          => ($locale === 'th' ? 'คำถามที่พบบ่อย' : ($locale === 'zh' ? '常见问题' : 'FAQ')),
        ] as $anchor => $label)
        <a href="{{ $anchor }}" class="text-xs font-medium bg-teal-50 text-teal-700 border border-teal-200 px-3 py-1.5 rounded-full hover:bg-teal-100 transition-colors">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- ── SECTION 1: What is TM30? ── --}}
    <section id="what-is-tm30">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-teal-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">1</span>
            {{ $locale === 'th' ? 'TM30 คืออะไร?' : ($locale === 'zh' ? '什么是TM30？' : 'What is TM30?') }}
        </h2>
        <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
            <p class="text-slate-700 leading-relaxed">
                @if($locale === 'th')
                    <strong>TM30</strong> คือแบบฟอร์มแจ้งที่พักอาศัยของคนต่างด้าวตาม <strong>มาตรา 38 แห่งพระราชบัญญัติคนเข้าเมือง พ.ศ. 2522</strong> กฎหมายนี้กำหนดให้เจ้าของหรือผู้ครอบครองสถานที่พัก แจ้งชื่อและสัญชาติของชาวต่างชาติที่เข้าพักต่อเจ้าหน้าที่ตรวจคนเข้าเมือง
                @elseif($locale === 'zh')
                    <strong>TM30</strong>是根据<strong>1979年《移民法》第38条</strong>要求填写的外国人住宿通知表。该法律要求住宿场所的所有者或占有者，向移民官员报告入住外国人的姓名和国籍。
                @else
                    <strong>TM30</strong> is the foreigner accommodation notification form required under <strong>Section 38 of the Immigration Act B.E. 2522 (1979)</strong>. This law requires owners or occupants of accommodation premises to report the names and nationalities of foreigners staying there to immigration authorities.
                @endif
            </p>
            <div class="grid sm:grid-cols-3 gap-4">
                @foreach([
                    ['icon'=>'ti-clock','color'=>'red',
                     'label_th'=>'กำหนดเวลา','label_en'=>'Deadline','label_zh'=>'截止时间',
                     'val_th'=>'ภายใน 24 ชั่วโมง','val_en'=>'Within 24 hours','val_zh'=>'24小时内'],
                    ['icon'=>'ti-coin','color'=>'amber',
                     'label_th'=>'ค่าธรรมเนียม','label_en'=>'Fee','label_zh'=>'费用',
                     'val_th'=>'ฟรี (ไม่มีค่าใช้จ่าย)','val_en'=>'Free of charge','val_zh'=>'免费'],
                    ['icon'=>'ti-device-laptop','color'=>'teal',
                     'label_th'=>'ช่องทาง','label_en'=>'Method','label_zh'=>'方式',
                     'val_th'=>'ออนไลน์ หรือ ที่สำนักงาน','val_en'=>'Online or at office','val_zh'=>'在线或亲自到办公室'],
                ] as $stat)
                <div class="bg-{{ $stat['color'] }}-50 border border-{{ $stat['color'] }}-200 rounded-xl p-4 text-center">
                    <i class="ti {{ $stat['icon'] }} text-{{ $stat['color'] }}-600 text-2xl block mb-1"></i>
                    <p class="text-xs text-{{ $stat['color'] }}-600 font-semibold uppercase tracking-wide mb-1">{{ $locale === 'th' ? $stat['label_th'] : ($locale === 'zh' ? $stat['label_zh'] : $stat['label_en']) }}</p>
                    <p class="text-sm font-bold text-slate-800">{{ $locale === 'th' ? $stat['val_th'] : ($locale === 'zh' ? $stat['val_zh'] : $stat['val_en']) }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── SECTION 2: How to File (Step-by-step) ── --}}
    <section id="how-to-file">
        <h2 class="text-xl font-bold text-slate-800 mb-2 flex items-center gap-2">
            <span class="w-8 h-8 bg-teal-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">2</span>
            {{ $locale === 'th' ? 'ขั้นตอนการแจ้ง TM30' : ($locale === 'zh' ? '提交TM30的步骤' : 'How to File TM30 — Step by Step') }}
        </h2>
        <p class="text-sm text-slate-500 mb-5 ml-10">
            {{ $locale === 'th' ? 'ทำได้ทั้งทางออนไลน์หรือที่สำนักงานตรวจคนเข้าเมือง' : ($locale === 'zh' ? '可在线或在移民局办公室办理' : 'Can be done online or at the Immigration Bureau') }}
        </p>
        <div class="space-y-3">
            @foreach([
                [
                    'n'=>1,'icon'=>'ti-user-check','color'=>'teal',
                    'th'=>'ตรวจสอบว่าคุณมีหน้าที่ต้องแจ้งหรือไม่',
                    'en'=>'Confirm you are obligated to file',
                    'zh'=>'确认您是否有义务申报',
                    'detail_th'=>'หากคุณให้ชาวต่างชาติพักอาศัยในสถานที่ของคุณไม่ว่าจะเป็นโรงแรม บ้านเช่า หรือที่อยู่อาศัย คุณมีหน้าที่ตามกฎหมายต้องแจ้ง TM30 ทุกครั้งที่ชาวต่างชาติเข้าพัก',
                    'detail_en'=>'If a foreigner stays at your premises — whether hotel, rental, or home — you are legally required to file TM30 every time they check in.',
                    'detail_zh'=>'如果外国人在您的场所住宿——无论是酒店、出租房还是住宅——您有法律义务在每次入住时提交TM30。',
                ],
                [
                    'n'=>2,'icon'=>'ti-files','color'=>'blue',
                    'th'=>'เตรียมข้อมูลที่ต้องใช้',
                    'en'=>'Gather the required information',
                    'zh'=>'收集所需信息',
                    'detail_th'=>'ข้อมูลที่ต้องกรอก: ชื่อ-นามสกุล สัญชาติ เลขหนังสือเดินทาง ประเภทวีซ่า วันที่เข้าพัก และที่อยู่ที่พักในไทย',
                    'detail_en'=>'Information needed: full name, nationality, passport number, visa type, check-in date, and accommodation address in Thailand.',
                    'detail_zh'=>'所需信息：全名、国籍、护照号码、签证类型、入住日期和泰国住宿地址。',
                ],
                [
                    'n'=>3,'icon'=>'ti-device-laptop','color'=>'purple',
                    'th'=>'เลือกช่องทางการแจ้ง: ออนไลน์ หรือ ที่สำนักงาน',
                    'en'=>'Choose filing method: Online or In-Person',
                    'zh'=>'选择申报方式：在线或亲自前往',
                    'detail_th'=>'แนะนำวิธีออนไลน์ที่ tm30.immigration.go.th — สะดวก รวดเร็ว ทำได้ทุกที่ทุกเวลา ไม่ต้องเสียเวลาเดินทาง หรือจะยื่นด้วยตนเองที่สำนักงานตรวจคนเข้าเมืองก็ได้',
                    'detail_en'=>'Online filing at tm30.immigration.go.th is recommended — fast, convenient, available 24/7. Alternatively, file in person at your local Immigration Bureau.',
                    'detail_zh'=>'推荐在tm30.immigration.go.th在线申报——快速、方便、24/7可用。也可亲自前往当地移民局办理。',
                ],
                [
                    'n'=>4,'icon'=>'ti-forms','color'=>'orange',
                    'th'=>'กรอกแบบฟอร์ม TM30',
                    'en'=>'Complete the TM30 form',
                    'zh'=>'填写TM30表格',
                    'detail_th'=>'กรอกข้อมูลให้ครบถ้วนและถูกต้อง ได้แก่ ข้อมูลผู้แจ้ง (เจ้าของที่พัก) และข้อมูลของชาวต่างชาติแต่ละคน หากมีหลายคนให้ระบุแต่ละคนแยกกัน',
                    'detail_en'=>'Complete all fields accurately — your details as the notifier (accommodation owner) and each foreigner\'s details separately.',
                    'detail_zh'=>'准确填写所有字段——作为通知人（住宿所有者）的您的详细信息，以及每位外国人的单独信息。',
                ],
                [
                    'n'=>5,'icon'=>'ti-send','color'=>'green',
                    'th'=>'ส่งแบบฟอร์มและรับใบรับแจ้ง',
                    'en'=>'Submit the form and receive your receipt',
                    'zh'=>'提交表格并获取收据',
                    'detail_th'=>'หลังจากส่งออนไลน์ ระบบจะออกใบรับแจ้งอัตโนมัติ (PDF) ให้พิมพ์และเก็บไว้ หากยื่นที่สำนักงาน จะได้รับใบรับแจ้งจากเจ้าหน้าที่',
                    'detail_en'=>'After online submission, the system generates an automatic receipt (PDF) — print and keep it. In-person filers receive a stamped receipt from the officer.',
                    'detail_zh'=>'在线提交后，系统会自动生成收据（PDF）——打印并保存。亲自申报者将从官员处获得盖章收据。',
                ],
                [
                    'n'=>6,'icon'=>'ti-archive','color'=>'slate',
                    'th'=>'เก็บใบรับแจ้งไว้เป็นหลักฐาน',
                    'en'=>'Keep the receipt as proof',
                    'zh'=>'保留收据作为证明',
                    'detail_th'=>'เก็บใบรับแจ้งทั้งเวอร์ชันดิจิทัลและกระดาษไว้อย่างน้อย 1 ปี ในกรณีที่เจ้าหน้าที่ตรวจสอบหรือชาวต่างชาติต้องการใช้เพื่อต่ออายุวีซ่า',
                    'detail_en'=>'Keep both digital and paper copies for at least 1 year — in case of inspection or if the foreigner needs it for a visa extension.',
                    'detail_zh'=>'保留数字和纸质副本至少1年——以备检查或外国人需要用于签证延期。',
                ],
            ] as $step)
            <div class="flex gap-4 bg-white border border-slate-200 rounded-2xl p-5 hover:border-teal-200 transition-colors">
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

    {{-- ── SECTION 3: Online Filing ── --}}
    <section id="online">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-teal-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">3</span>
            {{ $locale === 'th' ? 'การแจ้ง TM30 ทางออนไลน์ (แนะนำ)' : ($locale === 'zh' ? '在线提交TM30（推荐）' : 'Filing TM30 Online (Recommended)') }}
        </h2>
        <div class="bg-teal-50 border border-teal-200 rounded-2xl p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-teal-800 mb-3 flex items-center gap-2 text-sm">
                        <i class="ti ti-device-laptop text-teal-600"></i>
                        {{ $locale === 'th' ? 'วิธีใช้ระบบออนไลน์' : ($locale === 'zh' ? '如何使用在线系统' : 'How to Use the Online System') }}
                    </h3>
                    <ol class="space-y-2">
                        @foreach([
                            ['th'=>'ไปที่ tm30.immigration.go.th','en'=>'Go to tm30.immigration.go.th','zh'=>'前往 tm30.immigration.go.th', 'link' => true],
                            ['th'=>'ลงทะเบียนบัญชีผู้ใช้ (ครั้งแรก)','en'=>'Register a user account (first time only)','zh'=>'注册用户账户（仅首次）'],
                            ['th'=>'ล็อกอินและเลือก "แจ้งที่พักอาศัยคนต่างด้าว"','en'=>'Log in and select "Report Foreigner Accommodation"','zh'=>'登录并选择"报告外国人住宿"'],
                            ['th'=>'กรอกข้อมูลหนังสือเดินทางและที่พัก','en'=>'Enter passport and accommodation details','zh'=>'输入护照和住宿详情'],
                            ['th'=>'ตรวจสอบและส่งแบบฟอร์ม','en'=>'Review and submit the form','zh'=>'检查并提交表格'],
                            ['th'=>'ดาวน์โหลดและพิมพ์ใบรับแจ้ง','en'=>'Download and print the receipt','zh'=>'下载并打印收据'],
                        ] as $i => $step)
                        <li class="flex items-start gap-2 text-xs text-teal-700">
                            <span class="w-5 h-5 bg-teal-600 text-white rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">{{ $i+1 }}</span>
                            @if($step['link'] ?? false)
                                <span>
                                    @if($locale === 'th') ไปที่
                                    @elseif($locale === 'zh') 前往
                                    @else Go to
                                    @endif
                                    <a href="https://tm30.immigration.go.th" target="_blank" rel="noopener" class="font-semibold text-teal-800 underline hover:text-teal-900">tm30.immigration.go.th</a>
                                </span>
                            @else
                                {{ $locale === 'th' ? $step['th'] : ($locale === 'zh' ? $step['zh'] : $step['en']) }}
                            @endif
                        </li>
                        @endforeach
                    </ol>
                </div>
                <div class="space-y-3">
                    <h3 class="font-semibold text-teal-800 mb-3 flex items-center gap-2 text-sm">
                        <i class="ti ti-star text-teal-600"></i>
                        {{ $locale === 'th' ? 'ข้อดีของการแจ้งออนไลน์' : ($locale === 'zh' ? '在线申报的优势' : 'Benefits of Online Filing') }}
                    </h3>
                    @foreach([
                        ['icon'=>'ti-clock-24','th'=>'ทำได้ตลอด 24 ชั่วโมง ทุกวัน','en'=>'Available 24/7 — no office hours','zh'=>'全天候24/7——无需等候开放时间'],
                        ['icon'=>'ti-car-off','th'=>'ไม่ต้องเดินทางไปสำนักงาน','en'=>'No need to travel to the office','zh'=>'无需前往办公室'],
                        ['icon'=>'ti-certificate','th'=>'ได้ใบรับแจ้งทันที (PDF)','en'=>'Instant receipt (PDF)','zh'=>'立即获取收据（PDF）'],
                        ['icon'=>'ti-history','th'=>'ดูประวัติการแจ้งย้อนหลังได้','en'=>'Access history of past notifications','zh'=>'可查看历史申报记录'],
                    ] as $benefit)
                    <div class="flex items-center gap-3 bg-white rounded-xl p-3 border border-teal-200">
                        <i class="ti {{ $benefit['icon'] }} text-teal-600 text-lg shrink-0"></i>
                        <p class="text-xs text-slate-700">{{ $locale === 'th' ? $benefit['th'] : ($locale === 'zh' ? $benefit['zh'] : $benefit['en']) }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <a href="https://tm30.immigration.go.th" target="_blank" rel="noopener"
               class="mt-5 inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-5 py-3 rounded-xl transition-colors">
                <i class="ti ti-device-laptop text-lg"></i>
                {{ $locale === 'th' ? 'แจ้ง TM30 ออนไลน์ทันที' : ($locale === 'zh' ? '立即在线提交TM30' : 'File TM30 Online Now') }}
                <i class="ti ti-external-link text-sm"></i>
            </a>
        </div>
    </section>

    {{-- ── SECTION 4: Deadline ── --}}
    <section id="deadline">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-teal-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">4</span>
            {{ $locale === 'th' ? 'กำหนดเวลาและเงื่อนไข' : ($locale === 'zh' ? '截止时间和条件' : 'Deadlines & Requirements') }}
        </h2>
        <div class="grid md:grid-cols-3 gap-4">
            @foreach([
                [
                    'icon'=>'ti-clock','color'=>'red','border'=>'border-red-300',
                    'title_th'=>'ภายใน 24 ชั่วโมง','title_en'=>'Within 24 Hours','title_zh'=>'24小时内',
                    'desc_th'=>'นับจากที่ชาวต่างชาติเข้าพักในสถานที่ของคุณ รวมถึงกรณีที่เดินทางกลับมาจากต่างประเทศหรือย้ายที่พัก',
                    'desc_en'=>'From the moment the foreigner arrives at your premises, including returns from abroad or moving to a new address.',
                    'desc_zh'=>'从外国人抵达您的场所起算，包括从国外返回或搬至新地址的情况。',
                ],
                [
                    'icon'=>'ti-refresh','color'=>'blue','border'=>'border-blue-200',
                    'title_th'=>'ต้องแจ้งใหม่ทุกครั้ง','title_en'=>'File Every Time','title_zh'=>'每次都需申报',
                    'desc_th'=>'ไม่ใช่แค่ครั้งแรก — ทุกครั้งที่ชาวต่างชาติออกไปแล้วกลับมา หรือย้ายที่พัก ต้องแจ้งใหม่ภายใน 24 ชั่วโมง',
                    'desc_en'=>'Not just the first time — every time a foreigner leaves and returns, or moves to a new location, must notify again within 24 hours.',
                    'desc_zh'=>'不仅仅是第一次——每次外国人离开后返回，或搬到新地点，都必须在24小时内重新通知。',
                ],
                [
                    'icon'=>'ti-map-pin','color'=>'teal','border'=>'border-teal-200',
                    'title_th'=>'แจ้งที่ไหน','title_en'=>'Where to Report','title_zh'=>'在哪里报告',
                    'desc_th'=>'ทางออนไลน์ที่ tm30.immigration.go.th หรือที่สำนักงานตรวจคนเข้าเมืองที่รับผิดชอบพื้นที่นั้น',
                    'desc_en'=>'Online at tm30.immigration.go.th or at the Immigration Bureau office responsible for your area.',
                    'desc_zh'=>'在线：tm30.immigration.go.th，或前往负责您所在区域的移民局办公室。',
                ],
            ] as $d)
            <div class="bg-white border {{ $d['border'] }} rounded-2xl p-5">
                <div class="w-10 h-10 bg-{{ $d['color'] }}-100 rounded-xl flex items-center justify-center mb-3">
                    <i class="ti {{ $d['icon'] }} text-{{ $d['color'] }}-600 text-xl"></i>
                </div>
                <h3 class="font-bold text-slate-800 mb-2 text-sm">{{ $locale === 'th' ? $d['title_th'] : ($locale === 'zh' ? $d['title_zh'] : $d['title_en']) }}</h3>
                <p class="text-xs text-slate-500 leading-relaxed">{{ $locale === 'th' ? $d['desc_th'] : ($locale === 'zh' ? $d['desc_zh'] : $d['desc_en']) }}</p>
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
                        {{ $locale === 'th' ? '⚠️ บทลงโทษสำหรับผู้ไม่แจ้ง TM30' : ($locale === 'zh' ? '⚠️ 不提交TM30的处罚' : '⚠️ Penalties for Not Filing TM30') }}
                    </h2>
                    <div class="grid sm:grid-cols-2 gap-3 mb-4">
                        @foreach([
                            ['icon'=>'ti-coin','color'=>'red',
                             'th'=>'ค่าปรับสูงสุด ฿2,000 บาท','en'=>'Fine up to ฿2,000','zh'=>'罚款最高2,000泰铢',
                             'desc_th'=>'สำหรับเจ้าของที่พักที่ไม่ยื่นแบบฟอร์มภายในกำหนด','desc_en'=>'For accommodation owners who fail to file on time','desc_zh'=>'针对未按时申报的住宿所有者'],
                            ['icon'=>'ti-ban','color'=>'orange',
                             'th'=>'ชาวต่างชาติถูกปฏิเสธการต่อวีซ่า','en'=>'Foreigner may be denied visa extension','zh'=>'外国人可能被拒绝签证延期',
                             'desc_th'=>'หากไม่มีใบรับแจ้ง TM30 เจ้าหน้าที่อาจปฏิเสธการต่ออายุวีซ่า','desc_en'=>'Without a TM30 receipt, officers may refuse to process a visa extension','desc_zh'=>'没有TM30收据，官员可能拒绝处理签证延期'],
                            ['icon'=>'ti-alert-circle','color'=>'red',
                             'th'=>'ความผิดซ้ำถูกดำเนินคดีอาญา','en'=>'Repeat offenders face criminal charges','zh'=>'累犯面临刑事指控',
                             'desc_th'=>'การละเมิดซ้ำๆ อาจนำไปสู่การดำเนินคดีทางอาญาและโทษปรับที่สูงขึ้น','desc_en'=>'Repeated violations may lead to criminal prosecution and higher fines','desc_zh'=>'屡次违规可能导致刑事起诉和更高罚款'],
                            ['icon'=>'ti-shield-off','color'=>'purple',
                             'th'=>'ส่งผลต่อการต่อใบอนุญาตธุรกิจ','en'=>'May affect business license renewals','zh'=>'可能影响营业执照续期',
                             'desc_th'=>'โรงแรมและธุรกิจที่ไม่ปฏิบัติตามอาจถูกตรวจสอบและส่งผลต่อใบอนุญาต','desc_en'=>'Hotels and businesses that do not comply may face inspections affecting licenses','desc_zh'=>'不合规的酒店和企业可能面临检查，影响营业执照'],
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
                        {{ $locale === 'th' ? 'แนะนำ: แจ้ง TM30 ทันทีที่ชาวต่างชาติเช็คอิน อย่ารอจนครบ 24 ชั่วโมง' : ($locale === 'zh' ? '建议：外国人一办理入住手续就立即提交TM30，不要等到24小时截止时间。' : 'Tip: File TM30 immediately when the foreigner checks in — don\'t wait until the 24-hour deadline.') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── SECTION 6: FAQ ── --}}
    <section id="faq">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-teal-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">6</span>
            {{ $locale === 'th' ? 'คำถามที่พบบ่อย' : ($locale === 'zh' ? '常见问题' : 'Frequently Asked Questions') }}
        </h2>
        <div class="space-y-3" x-data="{open: null}">
            @foreach([
                [
                    'q_th'=>'ถ้าชาวต่างชาติพักแค่คืนเดียว ต้องแจ้งไหม?',
                    'q_en'=>'If a foreigner stays only one night, do I still need to file?',
                    'q_zh'=>'如果外国人只住一晚，我还需要申报吗？',
                    'a_th'=>'ใช่ ต้องแจ้งทุกกรณีไม่ว่าจะพักแค่คืนเดียวหรือหลายเดือน กฎหมายไม่ได้กำหนดระยะเวลาขั้นต่ำ',
                    'a_en'=>'Yes. You must file regardless of length of stay — one night or several months. The law sets no minimum stay threshold.',
                    'a_zh'=>'是的。无论停留时间长短——一晚或几个月——都必须申报。法律没有设定最短停留门槛。',
                ],
                [
                    'q_th'=>'ถ้าชาวต่างชาติออกไปแล้วกลับมา ต้องแจ้งใหม่อีกไหม?',
                    'q_en'=>'If the foreigner leaves and comes back, do I need to file again?',
                    'q_zh'=>'如果外国人离开后返回，我需要再次申报吗？',
                    'a_th'=>'ใช่ ทุกครั้งที่ชาวต่างชาติออกจากสถานที่และกลับมา (แม้แต่กลับจาก border run) ต้องแจ้งใหม่ภายใน 24 ชั่วโมง',
                    'a_en'=>'Yes. Every time the foreigner leaves and returns — even from a border run — you must file a new TM30 within 24 hours.',
                    'a_zh'=>'是的。每次外国人离开后返回——即使是边境游之后——都必须在24小时内重新提交TM30。',
                ],
                [
                    'q_th'=>'ชาวต่างชาติต้องแจ้งเอง หรือเจ้าของที่พักต้องแจ้ง?',
                    'q_en'=>'Does the foreigner file TM30, or does the accommodation owner?',
                    'q_zh'=>'是外国人提交TM30，还是住宿所有者提交？',
                    'a_th'=>'เจ้าของที่พักหรือผู้ครอบครองสถานที่เป็นผู้มีหน้าที่ตามกฎหมาย ชาวต่างชาติไม่ใช่ผู้รับผิดชอบ แต่ชาวต่างชาติต้องการใบรับแจ้งเพื่อใช้ต่ออายุวีซ่า',
                    'a_en'=>'The accommodation owner or occupant is legally responsible — not the foreigner. However, the foreigner needs the TM30 receipt for their visa extension.',
                    'a_zh'=>'住宿所有者或占用者负有法律责任——而非外国人。但外国人需要TM30收据来办理签证延期。',
                ],
                [
                    'q_th'=>'ถ้าเพื่อนชาวต่างชาติมาค้างบ้านฉัน ต้องแจ้งไหม?',
                    'q_en'=>'If a foreign friend stays at my home, do I need to file?',
                    'q_zh'=>'如果外国朋友住在我家，我需要申报吗？',
                    'a_th'=>'ใช่ แม้แต่บ้านพักส่วนตัวที่มีชาวต่างชาติมาพักอาศัยก็ต้องแจ้ง TM30 กฎหมายครอบคลุมทุกประเภทที่พักอาศัย',
                    'a_en'=>'Yes. Even private homes where foreigners stay must file TM30. The law covers all types of accommodation.',
                    'a_zh'=>'是的。即使是有外国人入住的私人住宅也必须提交TM30。该法律涵盖所有类型的住宿。',
                ],
                [
                    'q_th'=>'แจ้งช้า จะมีโทษอย่างไร?',
                    'q_en'=>'What happens if I file late?',
                    'q_zh'=>'如果逾期申报会怎样？',
                    'a_th'=>'มีโทษปรับสูงสุด 2,000 บาท หากเจ้าหน้าที่ตรวจพบว่าแจ้งช้าหรือไม่ได้แจ้ง แนะนำให้แจ้งทันทีแม้ว่าจะเลยกำหนดไปแล้ว ดีกว่าไม่แจ้งเลย',
                    'a_en'=>'A fine of up to ฿2,000 applies if officers find you filed late or not at all. It is still better to file late than not at all.',
                    'a_zh'=>'如果官员发现您逾期申报或未申报，最高罚款2,000泰铢。迟报总比不报好。',
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
            <i class="ti ti-download text-teal-600"></i>
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
        <div class="bg-teal-600 rounded-2xl p-5 text-white flex items-center gap-4">
            <i class="ti ti-robot text-4xl opacity-80 shrink-0"></i>
            <div>
                <p class="font-bold mb-1">{{ $locale === 'th' ? 'ถามผู้ช่วย AI' : ($locale === 'zh' ? '询问AI助手' : 'Ask the AI Assistant') }}</p>
                <p class="text-teal-100 text-xs mb-3">{{ $locale === 'th' ? 'มีคำถามเกี่ยวกับ TM30? คลิกปุ่ม AI ที่มุมขวาล่าง' : ($locale === 'zh' ? '有关于TM30的问题？点击右下角的AI按钮' : 'Questions about TM30? Click the AI button in the bottom right') }}</p>
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
