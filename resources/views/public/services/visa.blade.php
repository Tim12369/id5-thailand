@extends('public.layouts.app')
@section('title', app()->getLocale() === 'th' ? 'คู่มือวีซ่าไทย — ขั้นตอนครบจบ' : (app()->getLocale() === 'zh' ? '泰国签证完整指南' : 'Thai Visa Guide — Complete Step-by-Step'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-slate-500 flex items-center gap-1.5" aria-label="breadcrumb">
        <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ $locale === 'th' ? 'หน้าแรก' : ($locale === 'zh' ? '首页' : 'Home') }}</a>
        <span>/</span>
        <a href="{{ route('public.services.index') }}" class="hover:text-blue-700">{{ $locale === 'th' ? 'บริการ' : ($locale === 'zh' ? '服务' : 'Services') }}</a>
        <span>/</span>
        <span>{{ $locale === 'th' ? 'วีซ่า' : ($locale === 'zh' ? '签证' : 'Visa') }}</span>
    </nav>

    {{-- ── Hero ── --}}
    <div class="relative bg-linear-to-br from-blue-900 via-blue-800 to-blue-700 text-white rounded-3xl p-8 overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image:url('data:image/svg+xml,<svg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'><g fill=\'none\' fill-rule=\'evenodd\'><g fill=\'%23ffffff\' fill-opacity=\'1\'><path d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/></g></g></svg>');"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                    <i class="ti ti-passport text-3xl text-white"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-bold">
                        {{ $locale === 'th' ? 'คู่มือวีซ่าไทย' : ($locale === 'zh' ? '泰国签证指南' : 'Thai Visa Guide') }}
                    </h1>
                    <p class="text-blue-200 text-sm">
                        {{ $locale === 'th' ? 'ขั้นตอนครบจบ เข้าใจง่าย สำหรับทุกคน' : ($locale === 'zh' ? '完整步骤，简单易懂，适合所有人' : 'Complete step-by-step — easy for everyone') }}
                    </p>
                </div>
            </div>
            <p class="text-blue-100 leading-relaxed max-w-2xl">
                @if($locale === 'th')
                    ไม่ว่าคุณจะเป็นนักท่องเที่ยว นักเรียน หรือผู้ที่ต้องการทำงานในไทย หน้านี้จะอธิบายขั้นตอนการขอวีซ่าจากต้นจนจบ พร้อมเอกสารที่ต้องใช้และค่าธรรมเนียม
                @elseif($locale === 'zh')
                    无论您是游客、学生还是工作者，本页面将从头到尾解释签证申请流程，包括所需文件和费用。
                @else
                    Whether you're a tourist, student, or worker — this page walks you through the entire Thai visa process from start to finish, including required documents and fees.
                @endif
            </p>
        </div>
    </div>

    {{-- ── Jump links ── --}}
    <div class="flex flex-wrap gap-2">
        @foreach([
            '#do-i-need-visa' => ($locale === 'th' ? 'ต้องขอวีซ่าไหม?' : ($locale === 'zh' ? '需要签证吗？' : 'Do I Need a Visa?')),
            '#visa-types'     => ($locale === 'th' ? 'ประเภทวีซ่า' : ($locale === 'zh' ? '签证类型' : 'Visa Types')),
            '#how-to-apply'   => ($locale === 'th' ? 'วิธีขอวีซ่า' : ($locale === 'zh' ? '如何申请' : 'How to Apply')),
            '#extend'         => ($locale === 'th' ? 'ต่ออายุวีซ่า' : ($locale === 'zh' ? '延长签证' : 'Extend Your Visa')),
            '#documents'      => ($locale === 'th' ? 'เอกสาร' : ($locale === 'zh' ? '所需文件' : 'Documents')),
            '#fees'           => ($locale === 'th' ? 'ค่าธรรมเนียม' : ($locale === 'zh' ? '费用' : 'Fees')),
            '#overstay'       => ($locale === 'th' ? 'โทษอยู่เกิน' : ($locale === 'zh' ? '逾期警告' : 'Overstay Warning')),
        ] as $anchor => $label)
        <a href="{{ $anchor }}" class="text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-full hover:bg-blue-100 transition-colors">
            {{ $label }}
        </a>
        @endforeach
    </div>

    {{-- ── SECTION 1: Do I need a visa? ── --}}
    <section id="do-i-need-visa">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">1</span>
            {{ $locale === 'th' ? 'ฉันต้องขอวีซ่าไหม?' : ($locale === 'zh' ? '我需要签证吗？' : 'Do I Need a Visa?') }}
        </h2>
        <div class="grid md:grid-cols-3 gap-4">
            {{-- Visa Exemption --}}
            <div class="bg-green-50 border border-green-200 rounded-2xl p-5">
                <div class="flex items-center gap-2 mb-3">
                    <i class="ti ti-circle-check text-green-600 text-xl"></i>
                    <h3 class="font-semibold text-green-800 text-sm">
                        {{ $locale === 'th' ? 'ยกเว้นวีซ่า (Visa Exemption)' : ($locale === 'zh' ? '免签证' : 'Visa Exemption') }}
                    </h3>
                </div>
                <p class="text-xs text-green-700 leading-relaxed mb-3">
                    @if($locale === 'th') พลเมืองจาก 64 ประเทศสามารถเข้าไทยโดยไม่ต้องขอวีซ่า อยู่ได้ 30 วัน (บางประเทศ 90 วัน) รวมถึง สหรัฐอเมริกา สหราชอาณาจักร ออสเตรเลีย สิงคโปร์ ญี่ปุ่น เกาหลี และอีกมาก
                    @elseif($locale === 'zh') 64个国家/地区的公民可以免签证进入泰国，停留30天（部分国家90天），包括美国、英国、澳大利亚、新加坡、日本、韩国等。
                    @else Citizens from 64 countries can enter Thailand without a visa for 30 days (some for 90 days). This includes the USA, UK, Australia, Singapore, Japan, South Korea, and more.
                    @endif
                </p>
                <div class="bg-green-100 rounded-lg p-2 text-xs text-green-800">
                    <i class="ti ti-clock mr-1"></i>{{ $locale === 'th' ? 'พัก: 30 วัน (ขยายได้ 30 วัน)' : ($locale === 'zh' ? '停留：30天（可延长30天）' : 'Stay: 30 days (extendable 30 days)') }}
                </div>
            </div>
            {{-- Visa on Arrival --}}
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                <div class="flex items-center gap-2 mb-3">
                    <i class="ti ti-plane-arrival text-amber-600 text-xl"></i>
                    <h3 class="font-semibold text-amber-800 text-sm">
                        {{ $locale === 'th' ? 'วีซ่าเมื่อถึง (Visa on Arrival)' : ($locale === 'zh' ? '落地签证' : 'Visa on Arrival') }}
                    </h3>
                </div>
                <p class="text-xs text-amber-700 leading-relaxed mb-3">
                    @if($locale === 'th') พลเมืองจาก 19 ประเทศ รวมถึง จีน อินเดีย รัสเซีย และประเทศในแอฟริกาบางส่วน สามารถขอวีซ่าได้เมื่อถึงสนามบิน
                    @elseif($locale === 'zh') 来自19个国家的公民（包括中国、印度、俄罗斯等）可在抵达机场时申请签证。
                    @else Citizens from 19 countries including China, India, and Russia can apply for a visa upon arrival at the airport.
                    @endif
                </p>
                <div class="bg-amber-100 rounded-lg p-2 text-xs text-amber-800">
                    <i class="ti ti-clock mr-1"></i>{{ $locale === 'th' ? 'พัก: 15 วัน | ค่าธรรมเนียม: ฿2,000' : ($locale === 'zh' ? '停留：15天 | 费用：2,000泰铢' : 'Stay: 15 days | Fee: ฿2,000') }}
                </div>
            </div>
            {{-- Must apply --}}
            <div class="bg-red-50 border border-red-200 rounded-2xl p-5">
                <div class="flex items-center gap-2 mb-3">
                    <i class="ti ti-file-certificate text-red-600 text-xl"></i>
                    <h3 class="font-semibold text-red-800 text-sm">
                        {{ $locale === 'th' ? 'ต้องขอวีซ่าล่วงหน้า' : ($locale === 'zh' ? '需要提前申请签证' : 'Must Apply in Advance') }}
                    </h3>
                </div>
                <p class="text-xs text-red-700 leading-relaxed mb-3">
                    @if($locale === 'th') หากประเทศของคุณไม่อยู่ในรายการข้างต้น หรือคุณต้องการอยู่นานกว่า 30 วัน คุณต้องขอวีซ่าจากสถานทูตไทยในประเทศของคุณก่อนเดินทาง
                    @elseif($locale === 'zh') 如果您的国家不在上述列表中，或您需要停留超过30天，则必须在出发前向泰国大使馆申请签证。
                    @else If your country is not listed above, or you plan to stay longer than 30 days, you must apply at a Thai embassy in your home country before traveling.
                    @endif
                </p>
                <div class="bg-red-100 rounded-lg p-2 text-xs text-red-800">
                    <i class="ti ti-building mr-1"></i>{{ $locale === 'th' ? 'ยื่นที่สถานทูตไทยในประเทศของคุณ' : ($locale === 'zh' ? '在您所在国的泰国大使馆申请' : 'Apply at Thai embassy in your country') }}
                </div>
            </div>
        </div>
    </section>

    {{-- ── SECTION 2: Visa Types ── --}}
    <section id="visa-types">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">2</span>
            {{ $locale === 'th' ? 'ประเภทวีซ่าที่พบบ่อย' : ($locale === 'zh' ? '常见签证类型' : 'Common Visa Types') }}
        </h2>
        <div class="space-y-3">
            @foreach([
                [
                    'code'    => 'TR',
                    'color'   => 'blue',
                    'name_th' => 'วีซ่านักท่องเที่ยว',
                    'name_en' => 'Tourist Visa',
                    'name_zh' => '旅游签证',
                    'who_th'  => 'นักท่องเที่ยวที่ต้องการอยู่นานกว่า 30 วัน หรือประเทศที่ต้องขอวีซ่า',
                    'who_en'  => 'Tourists staying longer than 30 days or from countries that require a visa',
                    'who_zh'  => '需要停留超过30天的游客，或需要签证的国家公民',
                    'stay_th' => '60 วัน (ต่อได้ 30 วัน)',
                    'stay_en' => '60 days (extendable 30 days)',
                    'stay_zh' => '60天（可延长30天）',
                    'fee'     => '฿2,000',
                    'icon'    => 'ti-plane',
                ],
                [
                    'code'    => 'Non-B',
                    'color'   => 'teal',
                    'name_th' => 'วีซ่าทำงาน / ธุรกิจ',
                    'name_en' => 'Business / Work Visa',
                    'name_zh' => '商务/工作签证',
                    'who_th'  => 'ผู้ที่ต้องการทำงานหรือประกอบธุรกิจในประเทศไทย',
                    'who_en'  => 'Those who want to work or conduct business in Thailand',
                    'who_zh'  => '希望在泰国工作或经商的人',
                    'stay_th' => '90 วัน (ต่อได้ 1 ปี หากมีใบอนุญาตทำงาน)',
                    'stay_en' => '90 days (extendable 1 year with work permit)',
                    'stay_zh' => '90天（有工作许可证可延长1年）',
                    'fee'     => '฿2,000',
                    'icon'    => 'ti-briefcase',
                ],
                [
                    'code'    => 'Non-ED',
                    'color'   => 'purple',
                    'name_th' => 'วีซ่านักเรียน',
                    'name_en' => 'Student Visa',
                    'name_zh' => '学生签证',
                    'who_th'  => 'ผู้ที่ได้รับการตอบรับจากโรงเรียน มหาวิทยาลัย หรือสถาบันภาษาในไทย',
                    'who_en'  => 'Those accepted by a Thai school, university, or language institute',
                    'who_zh'  => '已被泰国学校、大学或语言学校录取的人',
                    'stay_th' => '90 วัน (ต่อได้ตามระยะเวลาที่เรียน)',
                    'stay_en' => '90 days (extendable for duration of study)',
                    'stay_zh' => '90天（可按学习期限延长）',
                    'fee'     => '฿2,000',
                    'icon'    => 'ti-school',
                ],
                [
                    'code'    => 'Non-O-A',
                    'color'   => 'green',
                    'name_th' => 'วีซ่าผู้เกษียณ',
                    'name_en' => 'Retirement Visa',
                    'name_zh' => '退休签证',
                    'who_th'  => 'ผู้ที่มีอายุ 50 ปีขึ้นไป ต้องการอยู่ในไทยระยะยาว มีเงินในบัญชีไม่ต่ำกว่า 800,000 บาท',
                    'who_en'  => 'Age 50+, wishing to live long-term in Thailand, with ฿800,000 in a Thai bank account',
                    'who_zh'  => '50岁以上，希望长期居住在泰国，泰国银行账户存款不低于80万泰铢',
                    'stay_th' => '1 ปี (ต่อได้ทุกปี)',
                    'stay_en' => '1 year (renewable annually)',
                    'stay_zh' => '1年（每年可续签）',
                    'fee'     => '฿2,000',
                    'icon'    => 'ti-heart',
                ],
                [
                    'code'    => 'Non-O',
                    'color'   => 'orange',
                    'name_th' => 'วีซ่าครอบครัว / คู่สมรส',
                    'name_en' => 'Family / Spouse Visa',
                    'name_zh' => '家庭/配偶签证',
                    'who_th'  => 'คู่สมรสหรือบุตรที่ถูกต้องตามกฎหมายของคนไทย หรือผู้ที่มีสมาชิกครอบครัวอยู่ในไทย',
                    'who_en'  => 'Legal spouse or child of a Thai national, or those with family members residing in Thailand',
                    'who_zh'  => '泰国公民的合法配偶或子女，或在泰国有家庭成员的人',
                    'stay_th' => '90 วัน (ต่อได้ 1 ปี)',
                    'stay_en' => '90 days (extendable 1 year)',
                    'stay_zh' => '90天（可延长1年）',
                    'fee'     => '฿2,000',
                    'icon'    => 'ti-users',
                ],
            ] as $v)
            <div class="bg-white border border-slate-200 rounded-2xl p-5 flex gap-4 items-start hover:border-blue-300 transition-colors">
                <div class="w-12 h-12 bg-{{ $v['color'] }}-50 border border-{{ $v['color'] }}-200 rounded-xl flex flex-col items-center justify-center shrink-0">
                    <i class="ti {{ $v['icon'] }} text-{{ $v['color'] }}-600 text-lg"></i>
                    <span class="text-{{ $v['color'] }}-700 text-[10px] font-bold leading-none mt-0.5">{{ $v['code'] }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <h3 class="font-semibold text-slate-800">
                            {{ $locale === 'th' ? $v['name_th'] : ($locale === 'zh' ? $v['name_zh'] : $v['name_en']) }}
                        </h3>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-mono">{{ $v['code'] }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mb-2">
                        {{ $locale === 'th' ? $v['who_th'] : ($locale === 'zh' ? $v['who_zh'] : $v['who_en']) }}
                    </p>
                    <div class="flex gap-3 flex-wrap text-xs">
                        <span class="text-slate-600"><i class="ti ti-clock mr-1 text-blue-500"></i>{{ $locale === 'th' ? $v['stay_th'] : ($locale === 'zh' ? $v['stay_zh'] : $v['stay_en']) }}</span>
                        <span class="text-slate-600"><i class="ti ti-coin mr-1 text-amber-500"></i>{{ $v['fee'] }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>

    {{-- ── SECTION 3: How to Apply (Step-by-step) ── --}}
    <section id="how-to-apply">
        <h2 class="text-xl font-bold text-slate-800 mb-2 flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">3</span>
            {{ $locale === 'th' ? 'ขั้นตอนการขอวีซ่า (ก่อนเดินทางมาไทย)' : ($locale === 'zh' ? '申请签证的步骤（出发前）' : 'How to Apply for a Visa (Before Arriving)') }}
        </h2>
        <p class="text-sm text-slate-500 mb-5 ml-10">
            {{ $locale === 'th' ? 'สำหรับผู้ที่ต้องการวีซ่าก่อนเดินทาง เช่น วีซ่านักเรียน วีซ่าทำงาน' : ($locale === 'zh' ? '适用于需要提前申请签证的人，如学生签证、工作签证' : 'For those who need a visa before traveling, such as student or work visas') }}
        </p>

        <div class="space-y-3">
            @foreach([
                [
                    'n'  => 1,
                    'th' => 'ตรวจสอบว่าคุณต้องขอวีซ่าหรือไม่',
                    'en' => 'Check whether you need a visa',
                    'zh' => '检查您是否需要签证',
                    'detail_th' => 'ตรวจสอบสัญชาติของคุณกับรายการยกเว้นวีซ่าไทย หากอยู่ในรายการ คุณสามารถเข้าไทยได้เลยโดยไม่ต้องขอวีซ่าล่วงหน้า',
                    'detail_en' => 'Check your nationality against Thailand\'s visa exemption list. If listed, you can enter Thailand without a prior visa.',
                    'detail_zh' => '对照泰国免签证名单检查您的国籍。如果在名单上，您可以直接入境泰国。',
                    'icon' => 'ti-search', 'color' => 'blue',
                ],
                [
                    'n'  => 2,
                    'th' => 'เลือกประเภทวีซ่าให้ถูกต้อง',
                    'en' => 'Choose the correct visa type',
                    'zh' => '选择正确的签证类型',
                    'detail_th' => 'วีซ่า TR สำหรับท่องเที่ยว, Non-B สำหรับทำงาน, Non-ED สำหรับเรียน, Non-O-A สำหรับผู้เกษียณ การขอวีซ่าผิดประเภทจะถูกปฏิเสธ',
                    'detail_en' => 'TR for tourism, Non-B for work, Non-ED for study, Non-O-A for retirement. Applying for the wrong type will result in rejection.',
                    'detail_zh' => 'TR用于旅游，Non-B用于工作，Non-ED用于学习，Non-O-A用于退休。申请错误类型将被拒绝。',
                    'icon' => 'ti-list-check', 'color' => 'purple',
                ],
                [
                    'n'  => 3,
                    'th' => 'เตรียมเอกสารให้ครบถ้วน',
                    'en' => 'Prepare all required documents',
                    'zh' => '准备所有所需文件',
                    'detail_th' => 'โดยทั่วไปต้องมี: หนังสือเดินทาง (อายุเหลืออย่างน้อย 6 เดือน), รูปถ่าย, แบบฟอร์มใบสมัคร, หลักฐานการเงิน, ตั๋วเครื่องบิน, และที่พัก ดูรายละเอียดในส่วน "เอกสาร" ด้านล่าง',
                    'detail_en' => 'Generally required: Passport (6+ months validity), photos, application form, financial proof, flight tickets, and accommodation. See "Documents" section below.',
                    'detail_zh' => '一般需要：护照（有效期6个月以上）、照片、申请表、财务证明、机票和住宿证明。详见下方"文件"部分。',
                    'icon' => 'ti-files', 'color' => 'teal',
                ],
                [
                    'n'  => 4,
                    'th' => 'ยื่นเอกสารที่สถานทูตหรือกงสุลไทย',
                    'en' => 'Submit documents at a Thai Embassy or Consulate',
                    'zh' => '在泰国大使馆或领事馆提交文件',
                    'detail_th' => 'ยื่นด้วยตนเองที่สถานทูตหรือสถานกงสุลไทยในประเทศของคุณ บางแห่งรับนัดหมายออนไลน์ ตรวจสอบเวลาทำการก่อนไป',
                    'detail_en' => 'Submit in person at the Thai Embassy or Consulate in your country. Some accept online appointments. Check opening hours beforehand.',
                    'detail_zh' => '亲自前往您所在国的泰国大使馆或领事馆提交。部分机构接受在线预约。请提前查看开放时间。',
                    'icon' => 'ti-building-bank', 'color' => 'orange',
                ],
                [
                    'n'  => 5,
                    'th' => 'ชำระค่าธรรมเนียมวีซ่า',
                    'en' => 'Pay the visa fee',
                    'zh' => '支付签证费用',
                    'detail_th' => 'ค่าธรรมเนียมวีซ่าปกติ ฿2,000 บาท (ประมาณ $55 USD) ชำระ ณ วันยื่น ไม่คืนเงินหากถูกปฏิเสธ',
                    'detail_en' => 'Standard visa fee is ฿2,000 (~$55 USD), paid on the day of submission. Non-refundable if rejected.',
                    'detail_zh' => '标准签证费为2,000泰铢（约55美元），提交当日支付。被拒绝不退款。',
                    'icon' => 'ti-credit-card', 'color' => 'green',
                ],
                [
                    'n'  => 6,
                    'th' => 'รอผลและรับวีซ่า',
                    'en' => 'Wait for approval and collect your visa',
                    'zh' => '等待审批并领取签证',
                    'detail_th' => 'ใช้เวลาประมาณ 3–5 วันทำการ วีซ่าจะถูกประทับลงในหนังสือเดินทางของคุณ ตรวจสอบวันที่และประเภทให้ถูกต้องก่อนเดินทาง',
                    'detail_en' => 'Processing takes 3–5 business days. The visa is stamped into your passport. Check the date and type carefully before traveling.',
                    'detail_zh' => '处理时间约3-5个工作日。签证将盖章在您的护照上。出发前仔细检查日期和类型。',
                    'icon' => 'ti-clock', 'color' => 'indigo',
                ],
                [
                    'n'  => 7,
                    'th' => 'เดินทางเข้าประเทศไทยและผ่านด่านตรวจคนเข้าเมือง',
                    'en' => 'Travel to Thailand and pass through immigration',
                    'zh' => '前往泰国并通过入境审查',
                    'detail_th' => 'เมื่อถึงท่าอากาศยาน ยื่นหนังสือเดินทาง บัตรขาเข้า (TM.6 — แจกบนเครื่อง) และเอกสารที่เกี่ยวข้องต่อเจ้าหน้าที่ตรวจคนเข้าเมือง',
                    'detail_en' => 'On arrival, present your passport, Arrival Card (TM.6 — distributed on the plane), and supporting documents to the immigration officer.',
                    'detail_zh' => '抵达后，向移民官员出示护照、入境卡（TM.6 — 在飞机上分发）和相关文件。',
                    'icon' => 'ti-stamp', 'color' => 'blue',
                ],
            ] as $step)
            <div class="flex gap-4 bg-white border border-slate-200 rounded-2xl p-5 hover:border-blue-200 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-{{ $step['color'] }}-100 flex items-center justify-center shrink-0">
                    <i class="ti {{ $step['icon'] }} text-{{ $step['color'] }}-600 text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold text-{{ $step['color'] }}-600 uppercase tracking-wider">{{ $locale === 'th' ? 'ขั้นตอนที่' : ($locale === 'zh' ? '步骤' : 'Step') }} {{ $step['n'] }}</span>
                    </div>
                    <h3 class="font-semibold text-slate-800 text-sm mb-1">
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

    {{-- ── SECTION 4: Extend Your Visa ── --}}
    <section id="extend">
        <h2 class="text-xl font-bold text-slate-800 mb-2 flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">4</span>
            {{ $locale === 'th' ? 'การต่ออายุวีซ่าในไทย' : ($locale === 'zh' ? '在泰国延长签证' : 'Extending Your Visa Inside Thailand') }}
        </h2>
        <p class="text-sm text-slate-500 mb-5 ml-10">
            {{ $locale === 'th' ? 'ทำที่สำนักงานตรวจคนเข้าเมืองก่อนวีซ่าหมดอายุ' : ($locale === 'zh' ? '在签证到期前在移民局办理' : 'Done at the Immigration Bureau before your visa expires') }}
        </p>

        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 mb-4">
            <div class="flex items-start gap-3">
                <i class="ti ti-info-circle text-blue-600 text-xl mt-0.5 shrink-0"></i>
                <p class="text-sm text-blue-800">
                    @if($locale === 'th') วีซ่าท่องเที่ยวสามารถต่อได้ 30 วัน ค่าธรรมเนียม <strong>฿1,900</strong> ต้องยื่นก่อนวีซ่าหมดอายุ อย่างน้อย 1 วัน
                    @elseif($locale === 'zh') 旅游签证可延长30天，费用<strong>1,900泰铢</strong>，必须在签证到期前至少1天申请。
                    @else Tourist visas can be extended by 30 days for <strong>฿1,900</strong>. You must apply at least 1 day before your visa expires.
                    @endif
                </p>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-3">
            @foreach([
                ['n'=>1,'th'=>'ไปสำนักงานตรวจคนเข้าเมืองในพื้นที่ของคุณ','en'=>'Go to your local Immigration Bureau office','zh'=>'前往您当地的移民局办公室'],
                ['n'=>2,'th'=>'กรอกแบบฟอร์ม TM.7 (แบบขอต่ออายุ)','en'=>'Fill out Form TM.7 (Extension of Stay form)','zh'=>'填写TM.7表格（居留延长申请表）'],
                ['n'=>3,'th'=>'เตรียมหนังสือเดินทาง รูปถ่าย 1 ใบ และหลักฐานที่พัก','en'=>'Bring passport, 1 photo, and proof of accommodation','zh'=>'携带护照、1张照片和住宿证明'],
                ['n'=>4,'th'=>'ชำระค่าธรรมเนียม ฿1,900 และรับใบเสร็จ','en'=>'Pay ฿1,900 fee and receive receipt','zh'=>'支付1,900泰铢并获取收据'],
                ['n'=>5,'th'=>'รอประทับตราขยายระยะเวลาในหนังสือเดินทาง','en'=>'Wait for the extension stamp in your passport','zh'=>'等待护照上盖延长章'],
                ['n'=>6,'th'=>'ตรวจสอบวันหมดอายุใหม่ให้ถูกต้อง','en'=>'Verify the new expiry date is correct','zh'=>'核实新的到期日期是否正确'],
            ] as $s)
            <div class="flex items-center gap-3 bg-white border border-slate-200 rounded-xl p-3">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-lg text-xs font-bold flex items-center justify-center shrink-0">{{ $s['n'] }}</span>
                <p class="text-sm text-slate-700">{{ $locale === 'th' ? $s['th'] : ($locale === 'zh' ? $s['zh'] : $s['en']) }}</p>
            </div>
            @endforeach
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('public.services.extension') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-700 hover:text-blue-900">
                {{ $locale === 'th' ? 'ดูคู่มือการต่ออายุฉบับเต็ม' : ($locale === 'zh' ? '查看完整续签指南' : 'View full extension guide') }}
                <i class="ti ti-arrow-right"></i>
            </a>
        </div>
    </section>

    {{-- ── SECTION 5: Documents ── --}}
    <section id="documents">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">5</span>
            {{ $locale === 'th' ? 'เอกสารที่ต้องใช้' : ($locale === 'zh' ? '所需文件' : 'Required Documents') }}
        </h2>
        <div class="grid md:grid-cols-2 gap-6">
            {{-- Tourist --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <h3 class="font-semibold text-blue-700 mb-3 flex items-center gap-2 text-sm">
                    <i class="ti ti-plane"></i>
                    {{ $locale === 'th' ? 'วีซ่านักท่องเที่ยว (TR)' : ($locale === 'zh' ? '旅游签证 (TR)' : 'Tourist Visa (TR)') }}
                </h3>
                <ul class="space-y-2">
                    @foreach([
                        ['th'=>'หนังสือเดินทางที่มีอายุเหลืออย่างน้อย 6 เดือน','en'=>'Passport valid for at least 6 months','zh'=>'护照有效期至少6个月'],
                        ['th'=>'รูปถ่ายสี ขนาด 4×6 ซม. จำนวน 2 ใบ','en'=>'2 color photos, 4×6 cm','zh'=>'2张彩色照片，4×6厘米'],
                        ['th'=>'แบบฟอร์มวีซ่า (TM.86 หรือตามสถานทูตกำหนด)','en'=>'Visa application form (TM.86 or as required by embassy)','zh'=>'签证申请表（TM.86或大使馆要求的表格）'],
                        ['th'=>'ตั๋วเครื่องบินขาออก (หรือหลักฐานการเดินทาง)','en'=>'Return/onward flight ticket (or proof of travel)','zh'=>'返程/前往机票（或旅行证明）'],
                        ['th'=>'หลักฐานที่พัก (จองโรงแรม หรือที่อยู่ในไทย)','en'=>'Accommodation proof (hotel booking or Thai address)','zh'=>'住宿证明（酒店预订或泰国地址）'],
                        ['th'=>'หลักฐานการเงิน (Statement บัญชีธนาคาร 3 เดือน หรือเงินสด)','en'=>'Financial proof (3-month bank statement or cash funds)','zh'=>'财务证明（3个月银行流水或现金）'],
                    ] as $doc)
                    <li class="flex items-start gap-2 text-xs text-slate-600">
                        <i class="ti ti-square-check text-green-500 mt-0.5 shrink-0"></i>
                        {{ $locale === 'th' ? $doc['th'] : ($locale === 'zh' ? $doc['zh'] : $doc['en']) }}
                    </li>
                    @endforeach
                </ul>
            </div>
            {{-- Work/Business --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <h3 class="font-semibold text-teal-700 mb-3 flex items-center gap-2 text-sm">
                    <i class="ti ti-briefcase"></i>
                    {{ $locale === 'th' ? 'วีซ่าทำงาน (Non-B)' : ($locale === 'zh' ? '工作签证 (Non-B)' : 'Work Visa (Non-B)') }}
                </h3>
                <ul class="space-y-2">
                    @foreach([
                        ['th'=>'หนังสือเดินทางที่มีอายุเหลืออย่างน้อย 6 เดือน','en'=>'Passport valid for at least 6 months','zh'=>'护照有效期至少6个月'],
                        ['th'=>'รูปถ่าย 2 ใบ','en'=>'2 passport photos','zh'=>'2张护照照片'],
                        ['th'=>'จดหมายรับรองจากบริษัทในไทย (ต้นฉบับ)','en'=>'Invitation/offer letter from a Thai company (original)','zh'=>'泰国公司邀请/录用函（原件）'],
                        ['th'=>'สำเนาใบอนุญาตประกอบกิจการของบริษัท','en'=>'Copy of company business license','zh'=>'公司营业执照复印件'],
                        ['th'=>'หลักฐานการเงินส่วนตัว','en'=>'Personal financial proof','zh'=>'个人财务证明'],
                        ['th'=>'ใบอนุญาตทำงาน (Work Permit) — ขอหลังจากได้รับวีซ่า','en'=>'Work Permit — applied for after visa approval','zh'=>'工作许可证 — 签证批准后申请'],
                    ] as $doc)
                    <li class="flex items-start gap-2 text-xs text-slate-600">
                        <i class="ti ti-square-check text-green-500 mt-0.5 shrink-0"></i>
                        {{ $locale === 'th' ? $doc['th'] : ($locale === 'zh' ? $doc['zh'] : $doc['en']) }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- ── SECTION 6: Fees ── --}}
    <section id="fees">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">6</span>
            {{ $locale === 'th' ? 'ค่าธรรมเนียม' : ($locale === 'zh' ? '费用' : 'Fees') }}
        </h2>
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">{{ $locale === 'th' ? 'ประเภท' : ($locale === 'zh' ? '类型' : 'Type') }}</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">{{ $locale === 'th' ? 'รายการ' : ($locale === 'zh' ? '项目' : 'Service') }}</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">{{ $locale === 'th' ? 'ค่าธรรมเนียม' : ($locale === 'zh' ? '费用' : 'Fee') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach([
                        ['cat'=>'visa','th'=>'วีซ่า','en'=>'Visa','zh'=>'签证','service_th'=>'วีซ่าประเภทเดียว (Single Entry)','service_en'=>'Single Entry Visa','service_zh'=>'单次入境签证','fee'=>'฿2,000'],
                        ['cat'=>'visa','th'=>'วีซ่า','en'=>'Visa','zh'=>'签证','service_th'=>'วีซ่าหลายครั้ง (Multiple Entry)','service_en'=>'Multiple Entry Visa','service_zh'=>'多次入境签证','fee'=>'฿5,000'],
                        ['cat'=>'voa','th'=>'VOA','en'=>'VOA','zh'=>'落地签','service_th'=>'วีซ่าเมื่อถึง (Visa on Arrival)','service_en'=>'Visa on Arrival','service_zh'=>'落地签证','fee'=>'฿2,000'],
                        ['cat'=>'extend','th'=>'ต่อวีซ่า','en'=>'Extension','zh'=>'续签','service_th'=>'ต่ออายุระยะเวลาอยู่ในราชอาณาจักร','service_en'=>'Extension of Stay','service_zh'=>'居留延长','fee'=>'฿1,900'],
                        ['cat'=>'extend','th'=>'ต่อวีซ่า','en'=>'Extension','zh'=>'续签','service_th'=>'เปลี่ยนประเภทวีซ่า','service_en'=>'Change of Visa Type','service_zh'=>'更改签证类型','fee'=>'฿2,000'],
                        ['cat'=>'report','th'=>'รายงาน','en'=>'Reporting','zh'=>'报到','service_th'=>'รายงานตัว 90 วัน','service_en'=>'90-Day Reporting','service_zh'=>'90天报到','fee'=>'ฟรี / Free'],
                        ['cat'=>'report','th'=>'รายงาน','en'=>'Reporting','zh'=>'报到','service_th'=>'แจ้ง TM30','service_en'=>'TM30 Notification','service_zh'=>'TM30通知','fee'=>'ฟรี / Free'],
                        ['cat'=>'fine','th'=>'ค่าปรับ','en'=>'Fine','zh'=>'罚款','service_th'=>'อยู่เกินกำหนด (ต่อวัน)','service_en'=>'Overstay (per day)','service_zh'=>'逾期（每天）','fee'=>'฿500/วัน'],
                        ['cat'=>'fine','th'=>'ค่าปรับ','en'=>'Fine','zh'=>'罚款','service_th'=>'อยู่เกินกำหนดสูงสุด','service_en'=>'Overstay maximum fine','service_zh'=>'逾期最高罚款','fee'=>'฿20,000'],
                    ] as $fee)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3">
                            @php $catColors = ['visa'=>'blue','voa'=>'amber','extend'=>'teal','fine'=>'red','report'=>'slate']; @endphp
                            <span class="text-xs font-medium bg-{{ $catColors[$fee['cat']] }}-50 text-{{ $catColors[$fee['cat']] }}-700 border border-{{ $catColors[$fee['cat']] }}-200 px-2 py-0.5 rounded-full">
                                {{ $locale === 'th' ? $fee['th'] : ($locale === 'zh' ? $fee['zh'] : $fee['en']) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-700">{{ $locale === 'th' ? $fee['service_th'] : ($locale === 'zh' ? $fee['service_zh'] : $fee['service_en']) }}</td>
                        <td class="px-5 py-3 text-right font-semibold {{ $fee['cat'] === 'fine' ? 'text-red-600' : 'text-slate-800' }}">{{ $fee['fee'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ── SECTION 7: Overstay Warning ── --}}
    <section id="overstay">
        <div class="bg-red-50 border-2 border-red-300 rounded-2xl p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center shrink-0">
                    <i class="ti ti-alert-triangle text-red-600 text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-red-800 mb-2">
                        {{ $locale === 'th' ? '⚠️ คำเตือน: การอยู่เกินกำหนดวีซ่า' : ($locale === 'zh' ? '⚠️ 警告：签证逾期' : '⚠️ Warning: Overstaying Your Visa') }}
                    </h2>
                    <div class="space-y-2 text-sm text-red-700">
                        @if($locale === 'th')
                        <p><strong>การอยู่เกินกำหนดวีซ่าเป็นความผิดทางอาญาในประเทศไทย</strong></p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li>ค่าปรับ <strong>500 บาทต่อวัน</strong> สูงสุด 20,000 บาท</li>
                            <li>ถูกกักตัวและส่งตัวกลับประเทศ (Deportation)</li>
                            <li>ถูกห้ามกลับประเทศไทยเป็นระยะเวลา 1–10 ปี ขึ้นอยู่กับจำนวนวันที่เกิน</li>
                            <li>อยู่เกิน 90 วัน → ห้ามเข้า 1 ปี</li>
                            <li>อยู่เกิน 1 ปี → ห้ามเข้า 3 ปี</li>
                            <li>อยู่เกิน 3 ปี → ห้ามเข้า 10 ปี</li>
                        </ul>
                        <p class="mt-3 font-semibold">หากวีซ่าใกล้หมดอายุ ให้ต่ออายุก่อนหมดเขต หรือออกนอกประเทศแล้วกลับมาใหม่ (Border Run)</p>
                        @elseif($locale === 'zh')
                        <p><strong>在泰国逾期居留是刑事犯罪。</strong></p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li>每天罚款<strong>500泰铢</strong>，最高20,000泰铢</li>
                            <li>被拘留和驱逐出境</li>
                            <li>根据逾期天数，禁止入境泰国1-10年</li>
                        </ul>
                        @else
                        <p><strong>Overstaying is a criminal offense in Thailand.</strong></p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li>Fine of <strong>฿500 per day</strong>, up to ฿20,000</li>
                            <li>Detention and deportation</li>
                            <li>Re-entry ban of 1–10 years depending on length of overstay</li>
                            <li>Overstay 90+ days → 1-year ban</li>
                            <li>Overstay 1+ years → 3-year ban</li>
                            <li>Overstay 3+ years → 10-year ban</li>
                        </ul>
                        <p class="mt-3 font-semibold">If your visa is about to expire, extend it before it runs out or do a border run (leave and re-enter).</p>
                        @endif
                    </div>
                    <a href="{{ route('public.services.overstay') }}" class="inline-flex items-center gap-1 mt-4 text-xs font-medium bg-red-600 text-white px-4 py-2 rounded-xl hover:bg-red-700 transition-colors">
                        {{ $locale === 'th' ? 'อ่านเพิ่มเติมเรื่องการอยู่เกิน' : ($locale === 'zh' ? '了解更多关于逾期' : 'Read more about overstay') }}
                        <i class="ti ti-arrow-right text-sm"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ── Downloadable Forms ── --}}
    @if($forms->count())
    <section>
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i class="ti ti-download text-blue-600"></i>
            {{ $locale === 'th' ? 'แบบฟอร์มที่เกี่ยวข้อง' : ($locale === 'zh' ? '相关表格' : 'Downloadable Forms') }}
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
        <div class="bg-blue-600 rounded-2xl p-5 text-white flex items-center gap-4">
            <i class="ti ti-robot text-4xl opacity-80 shrink-0"></i>
            <div>
                <p class="font-bold mb-1">{{ $locale === 'th' ? 'ถามผู้ช่วย AI' : ($locale === 'zh' ? '询问AI助手' : 'Ask the AI Assistant') }}</p>
                <p class="text-blue-100 text-xs mb-3">{{ $locale === 'th' ? 'มีคำถามเฉพาะเจาะจง? คลิกปุ่ม AI ที่มุมขวาล่าง' : ($locale === 'zh' ? '有具体问题？点击右下角的AI按钮' : 'Have a specific question? Click the AI button in the bottom right') }}</p>
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
