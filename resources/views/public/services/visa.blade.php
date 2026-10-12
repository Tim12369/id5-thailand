@extends('public.layouts.app')
@section('title', __('visa.thai_visa_guide_complete_step_by'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-slate-500 flex items-center gap-1.5" aria-label="{{ __('layout.breadcrumb') }}">
        <a href="{{ route('public.home') }}" class="hover:text-blue-700">{{ __('visa.home') }}</a>
        <span>/</span>
        <a href="{{ route('public.services.index') }}" class="hover:text-blue-700">{{ __('visa.services') }}</a>
        <span>/</span>
        <span>{{ __('visa.visa') }}</span>
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
                        {{ __('visa.thai_visa_guide') }}
                    </h1>
                    <p class="text-blue-200 text-sm">
                        {{ __('visa.complete_step_by_step_easy_for') }}
                    </p>
                </div>
            </div>
            <p class="text-blue-100 leading-relaxed max-w-2xl">
                {{ __('visa.whether_you_re_a_tourist_student_or') }}
            </p>
        </div>
    </div>

    {{-- ── Jump links ── --}}
    <div class="flex flex-wrap gap-2">
        @foreach([
            '#do-i-need-visa' => (__('visa.do_i_need_a_visa')),
            '#visa-types'     => (__('visa.visa_types')),
            '#how-to-apply'   => (__('visa.how_to_apply')),
            '#extend'         => (__('visa.extend_your_visa')),
            '#documents'      => (__('visa.documents')),
            '#fees'           => (__('visa.fees')),
            '#overstay'       => (__('visa.overstay_warning')),
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
            {{ __('visa.do_i_need_a_visa_2') }}
        </h2>
        <div class="grid md:grid-cols-3 gap-4">
            {{-- Visa Exemption --}}
            <div class="bg-green-50 border border-green-200 rounded-2xl p-5">
                <div class="flex items-center gap-2 mb-3">
                    <i class="ti ti-circle-check text-green-600 text-xl"></i>
                    <h3 class="font-semibold text-green-800 text-sm">
                        {{ __('visa.visa_exemption') }}
                    </h3>
                </div>
                <p class="text-xs text-green-700 leading-relaxed mb-3">
                    {{ __('visa.citizens_from_64_countries_can') }}
                </p>
                <div class="bg-green-100 rounded-lg p-2 text-xs text-green-800">
                    <i class="ti ti-clock mr-1"></i>{{ __('visa.stay_30_days_extendable_30_days') }}
                </div>
            </div>
            {{-- Visa on Arrival --}}
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                <div class="flex items-center gap-2 mb-3">
                    <i class="ti ti-plane-arrival text-amber-600 text-xl"></i>
                    <h3 class="font-semibold text-amber-800 text-sm">
                        {{ __('visa.service_visa_on_arrival') }}
                    </h3>
                </div>
                <p class="text-xs text-amber-700 leading-relaxed mb-3">
                    {{ __('visa.citizens_from_19_countries') }}
                </p>
                <div class="bg-amber-100 rounded-lg p-2 text-xs text-amber-800">
                    <i class="ti ti-clock mr-1"></i>{{ __('visa.stay_15_days_fee_2_000') }}
                </div>
            </div>
            {{-- Must apply --}}
            <div class="bg-red-50 border border-red-200 rounded-2xl p-5">
                <div class="flex items-center gap-2 mb-3">
                    <i class="ti ti-file-certificate text-red-600 text-xl"></i>
                    <h3 class="font-semibold text-red-800 text-sm">
                        {{ __('visa.must_apply_in_advance') }}
                    </h3>
                </div>
                <p class="text-xs text-red-700 leading-relaxed mb-3">
                    {{ __('visa.if_your_country_is_not_listed_above') }}
                </p>
                <div class="bg-red-100 rounded-lg p-2 text-xs text-red-800">
                    <i class="ti ti-building mr-1"></i>{{ __('visa.apply_at_thai_embassy_in_your') }}
                </div>
            </div>
        </div>
    </section>

    {{-- ── SECTION 2: Visa Types ── --}}
    <section id="visa-types">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">2</span>
            {{ __('visa.common_visa_types') }}
        </h2>
        <div class="space-y-3">
            @foreach([
                [
                    'code'    => __('visa.code_tr'),
                    'color'   => 'blue',
                    'name' => __('visa.tourist_visa'),
                    'who'  => __('visa.who_tourists_staying_longer_than_30'),
                    'stay' => __('visa.stay_60_days_extendable_30_days'),
                    'fee'     => __('visa.fee_tr'),
                    'icon'    => 'ti-plane',
                ],
                [
                    'code'    => __('visa.code_non_b'),
                    'color'   => 'teal',
                    'name' => __('visa.business_work_visa'),
                    'who'  => __('visa.who_those_who_want_to_work_or_conduct'),
                    'stay' => __('visa.stay_90_days_extendable_1_year_with_work'),
                    'fee'     => __('visa.fee_non_b'),
                    'icon'    => 'ti-briefcase',
                ],
                [
                    'code'    => __('visa.code_non_ed'),
                    'color'   => 'purple',
                    'name' => __('visa.student_visa'),
                    'who'  => __('visa.who_those_accepted_by_a_thai_school'),
                    'stay' => __('visa.stay_90_days_extendable_for_duration_of'),
                    'fee'     => __('visa.fee_non_ed'),
                    'icon'    => 'ti-school',
                ],
                [
                    'code'    => __('visa.code_non_o_a'),
                    'color'   => 'green',
                    'name' => __('visa.retirement_visa'),
                    'who'  => __('visa.who_age_50_wishing_to_live_long_term_in'),
                    'stay' => __('visa.stay_1_year_renewable_annually'),
                    'fee'     => __('visa.fee_non_o_a'),
                    'icon'    => 'ti-heart',
                ],
                [
                    'code'    => __('visa.code_non_o'),
                    'color'   => 'orange',
                    'name' => __('visa.family_spouse_visa'),
                    'who'  => __('visa.who_legal_spouse_or_child_of_a_thai'),
                    'stay' => __('visa.stay_90_days_extendable_1_year'),
                    'fee'     => __('visa.fee_non_o'),
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
                            {{ $v['name'] }}
                        </h3>
                        <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-mono">{{ $v['code'] }}</span>
                    </div>
                    <p class="text-xs text-slate-500 mb-2">
                        {{ $v['who'] }}
                    </p>
                    <div class="flex gap-3 flex-wrap text-xs">
                        <span class="text-slate-600"><i class="ti ti-clock mr-1 text-blue-500"></i>{{ $v['stay'] }}</span>
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
            {{ __('visa.how_to_apply_for_a_visa_before') }}
        </h2>
        <p class="text-sm text-slate-500 mb-5 ml-10">
            {{ __('visa.for_those_who_need_a_visa_before') }}
        </p>

        <div class="space-y-3">
            @foreach([
                [
                    'n'  => 1,
                    'text' => __('visa.check_whether_you_need_a_visa'),
                    'detail' => __('visa.check_your_nationality_against'),
                    'icon' => 'ti-search', 'color' => 'blue',
                ],
                [
                    'n'  => 2,
                    'text' => __('visa.choose_the_correct_visa_type'),
                    'detail' => __('visa.tr_for_tourism_non_b_for_work_non'),
                    'icon' => 'ti-list-check', 'color' => 'purple',
                ],
                [
                    'n'  => 3,
                    'text' => __('visa.prepare_all_required_documents'),
                    'detail' => __('visa.generally_required_passport_6'),
                    'icon' => 'ti-files', 'color' => 'teal',
                ],
                [
                    'n'  => 4,
                    'text' => __('visa.submit_documents_at_a_thai_embassy'),
                    'detail' => __('visa.submit_in_person_at_the_thai'),
                    'icon' => 'ti-building-bank', 'color' => 'orange',
                ],
                [
                    'n'  => 5,
                    'text' => __('visa.pay_the_visa_fee'),
                    'detail' => __('visa.standard_visa_fee_is_2_000_55_usd'),
                    'icon' => 'ti-credit-card', 'color' => 'green',
                ],
                [
                    'n'  => 6,
                    'text' => __('visa.wait_for_approval_and_collect_your'),
                    'detail' => __('visa.processing_takes_3_5_business_days'),
                    'icon' => 'ti-clock', 'color' => 'indigo',
                ],
                [
                    'n'  => 7,
                    'text' => __('visa.travel_to_thailand_and_pass_through'),
                    'detail' => __('visa.on_arrival_present_your_passport'),
                    'icon' => 'ti-stamp', 'color' => 'blue',
                ],
            ] as $step)
            <div class="flex gap-4 bg-white border border-slate-200 rounded-2xl p-5 hover:border-blue-200 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-{{ $step['color'] }}-100 flex items-center justify-center shrink-0">
                    <i class="ti {{ $step['icon'] }} text-{{ $step['color'] }}-600 text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold text-{{ $step['color'] }}-600 uppercase tracking-wider">{{ __('visa.step') }} {{ $step['n'] }}</span>
                    </div>
                    <h3 class="font-semibold text-slate-800 text-sm mb-1">
                        {{ $step['text'] }}
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        {{ $step['detail'] }}
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
            {{ __('visa.extending_your_visa_inside_thailand') }}
        </h2>
        <p class="text-sm text-slate-500 mb-5 ml-10">
            {{ __('visa.done_at_the_immigration_bureau') }}
        </p>

        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 mb-4">
            <div class="flex items-start gap-3">
                <i class="ti ti-info-circle text-blue-600 text-xl mt-0.5 shrink-0"></i>
                <p class="text-sm text-blue-800">
                    {!! __('visa.tourist_visas_can_be_extended_by_30_html') !!}
                </p>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-3">
            @foreach([
                ['n'=>1,'text'=>__('visa.go_to_your_local_immigration_bureau')],
                ['n'=>2,'text'=>__('visa.fill_out_form_tm_7_extension_of')],
                ['n'=>3,'text'=>__('visa.bring_passport_1_photo_and_proof_of')],
                ['n'=>4,'text'=>__('visa.pay_1_900_fee_and_receive_receipt')],
                ['n'=>5,'text'=>__('visa.wait_for_the_extension_stamp_in')],
                ['n'=>6,'text'=>__('visa.verify_the_new_expiry_date_is')],
            ] as $s)
            <div class="flex items-center gap-3 bg-white border border-slate-200 rounded-xl p-3">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-lg text-xs font-bold flex items-center justify-center shrink-0">{{ $s['n'] }}</span>
                <p class="text-sm text-slate-700">{{ $s['text'] }}</p>
            </div>
            @endforeach
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('public.services.extension') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-700 hover:text-blue-900">
                {{ __('visa.view_full_extension_guide') }}
                <i class="ti ti-arrow-right"></i>
            </a>
        </div>
    </section>

    {{-- ── SECTION 5: Documents ── --}}
    <section id="documents">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">5</span>
            {{ __('visa.required_documents') }}
        </h2>
        <div class="grid md:grid-cols-2 gap-6">
            {{-- Tourist --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <h3 class="font-semibold text-blue-700 mb-3 flex items-center gap-2 text-sm">
                    <i class="ti ti-plane"></i>
                    {{ __('visa.tourist_visa_tr') }}
                </h3>
                <ul class="space-y-2">
                    @foreach([
                        ['text'=>__('visa.passport_valid_for_at_least_6')],
                        ['text'=>__('visa.2_color_photos_4_6_cm')],
                        ['text'=>__('visa.visa_application_form_tm_86_or_as')],
                        ['text'=>__('visa.return_onward_flight_ticket_or')],
                        ['text'=>__('visa.accommodation_proof_hotel_booking')],
                        ['text'=>__('visa.financial_proof_3_month_bank')],
                    ] as $doc)
                    <li class="flex items-start gap-2 text-xs text-slate-600">
                        <i class="ti ti-square-check text-green-500 mt-0.5 shrink-0"></i>
                        {{ $doc['text'] }}
                    </li>
                    @endforeach
                </ul>
            </div>
            {{-- Work/Business --}}
            <div class="bg-white border border-slate-200 rounded-2xl p-5">
                <h3 class="font-semibold text-teal-700 mb-3 flex items-center gap-2 text-sm">
                    <i class="ti ti-briefcase"></i>
                    {{ __('visa.work_visa_non_b') }}
                </h3>
                <ul class="space-y-2">
                    @foreach([
                        ['text'=>__('visa.passport_valid_for_at_least_6')],
                        ['text'=>__('visa.2_passport_photos')],
                        ['text'=>__('visa.invitation_offer_letter_from_a_thai')],
                        ['text'=>__('visa.copy_of_company_business_license')],
                        ['text'=>__('visa.personal_financial_proof')],
                        ['text'=>__('visa.work_permit_applied_for_after_visa')],
                    ] as $doc)
                    <li class="flex items-start gap-2 text-xs text-slate-600">
                        <i class="ti ti-square-check text-green-500 mt-0.5 shrink-0"></i>
                        {{ $doc['text'] }}
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
            {{ __('visa.fees') }}
        </h2>
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">{{ __('visa.type') }}</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">{{ __('visa.service') }}</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-600 uppercase tracking-wider">{{ __('visa.fee') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach([
                        ['cat'=>'visa','text'=>__('visa.visa'),'service'=>__('visa.service_single_entry_visa'),'fee'=>__('visa.price_single_entry')],
                        ['cat'=>'visa','text'=>__('visa.visa'),'service'=>__('visa.service_multiple_entry_visa'),'fee'=>__('visa.price_multiple_entry')],
                        ['cat'=>'voa','text'=>__('visa.voa'),'service'=>__('visa.service_visa_on_arrival'),'fee'=>__('visa.price_visa_on_arrival')],
                        ['cat'=>'extend','text'=>__('visa.extension'),'service'=>__('visa.service_extension_of_stay'),'fee'=>__('visa.price_extension_of_stay')],
                        ['cat'=>'extend','text'=>__('visa.extension'),'service'=>__('visa.service_change_of_visa_type'),'fee'=>__('visa.price_change_of_type')],
                        ['cat'=>'report','text'=>__('visa.reporting'),'service'=>__('visa.service_90_day_reporting'),'fee'=>__('visa.price_90_day_reporting')],
                        ['cat'=>'report','text'=>__('visa.reporting'),'service'=>__('visa.service_tm30_notification'),'fee'=>__('visa.price_tm30_notification')],
                        ['cat'=>'fine','text'=>__('visa.fine'),'service'=>__('visa.service_overstay_per_day'),'fee'=>__('visa.price_overstay_per_day')],
                        ['cat'=>'fine','text'=>__('visa.fine'),'service'=>__('visa.service_overstay_maximum_fine'),'fee'=>__('visa.price_overstay_maximum')],
                    ] as $fee)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3">
                            @php $catColors = ['visa'=>'blue','voa'=>'amber','extend'=>'teal','fine'=>'red','report'=>'slate']; @endphp
                            <span class="text-xs font-medium bg-{{ $catColors[$fee['cat']] }}-50 text-{{ $catColors[$fee['cat']] }}-700 border border-{{ $catColors[$fee['cat']] }}-200 px-2 py-0.5 rounded-full">
                                {{ $fee['text'] }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-700">{{ $fee['service'] }}</td>
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
                        {{ __('visa.warning_overstaying_your_visa') }}
                    </h2>
                    <div class="space-y-2 text-sm text-red-700">
                        <p><strong>{{ __('visa.overstay_is_a_crime') }}</strong></p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li>{!! __('visa.overstay_fine_html') !!}</li>
                            <li>{{ __('visa.overstay_detention') }}</li>
                            <li>{{ __('visa.overstay_reentry_ban') }}</li>
                            {{-- The Chinese wording, which no page serves, stops here. --}}
                            @if($locale !== 'zh')
                            <li>{{ __('visa.overstay_ban_90_days') }}</li>
                            <li>{{ __('visa.overstay_ban_1_year') }}</li>
                            <li>{{ __('visa.overstay_ban_3_years') }}</li>
                            @endif
                        </ul>
                        @if($locale !== 'zh')
                        <p class="mt-3 font-semibold">{{ __('visa.overstay_advice') }}</p>
                        @endif
                    </div>
                    <a href="{{ route('public.services.overstay') }}" class="inline-flex items-center gap-1 mt-4 text-xs font-medium bg-red-600 text-white px-4 py-2 rounded-xl hover:bg-red-700 transition-colors">
                        {{ __('visa.read_more_about_overstay') }}
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
            {{ __('visa.downloadable_forms') }}
        </h2>
        <div class="space-y-2">
            @foreach($forms as $form)
            @cmsEdit($form)<div class="flex items-center gap-4 p-3 bg-white rounded-xl border border-slate-200">
                <i class="ti ti-file-type-pdf text-red-600 text-xl shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800">{{ $form->getLocalizedName() }}</p>
                    <p class="text-xs text-slate-400">{{ $form->code }} · v{{ $form->version }}</p>
                </div>
                <a href="{{ route('public.downloads.download', $form->id) }}"
                   class="text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors" download>
                    <i class="ti ti-download mr-1"></i>{{ __('services.pdf') }}
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
                <p class="font-bold mb-1">{{ __('visa.ask_the_ai_assistant') }}</p>
                <p class="text-blue-100 text-xs mb-3">{{ __('visa.have_a_specific_question_click_the') }}</p>
                <span class="text-xs bg-white/20 rounded-lg px-3 py-1">{{ __('visa.available_24_7') }}</span>
            </div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-center gap-4">
            <i class="ti ti-phone text-green-600 text-4xl shrink-0"></i>
            <div>
                <p class="font-bold text-slate-800 mb-1">{{ __('visa.immigration_hotline') }}</p>
                @cmsEdit('setting:identity', 'สายด่วน', 'หมายเลขสายด่วน')<p class="text-3xl font-bold text-green-600 mb-1">{{ $hotline }}</p>
                <p class="text-xs text-slate-500">{{ __('visa.24_hours_a_day') }}</p>
            </div>
        </div>
    </div>

</div>
@endsection
