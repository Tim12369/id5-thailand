@extends('public.layouts.app')
@section('title', __('90day.90_day_report_guide_complete_step'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-slate-500 flex items-center gap-1.5">
        <a href="{{ route('public.home') }}" class="hover:text-indigo-700">{{ __('90day.home') }}</a>
        <span>/</span>
        <a href="{{ route('public.services.index') }}" class="hover:text-indigo-700">{{ __('90day.services') }}</a>
        <span>/</span>
        <span>{{ __('90day.90_day_report') }}</span>
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
                        <span class="bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ __('90day.form_badge') }}</span>
                    </div>
                    <h1 class="text-3xl font-bold">
                        {{ __('90day.90_day_stay_notification') }}
                    </h1>
                    <p class="text-indigo-200 text-sm mt-1">
                        {{ __('90day.required_for_all_foreigners_on_long') }}
                    </p>
                </div>
            </div>
            <p class="text-indigo-100 leading-relaxed max-w-2xl">
                {!! __('90day.foreigners_staying_in_thailand_for_html') !!}
            </p>
        </div>
    </div>

    {{-- ── Quick Stats ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach([
            ['icon'=>'ti-calendar','color'=>'indigo',
             'label'=>__('90day.report_every'),
             'val'=>__('90day.90_days')],
            ['icon'=>'ti-coin','color'=>'green',
             'label'=>__('90day.fee'),
             'val'=>__('90day.free_of_charge')],
            ['icon'=>'ti-alert-triangle','color'=>'red',
             'label'=>__('90day.late_fine'),
             'val'=>__('90day.2_000')],
            ['icon'=>'ti-device-laptop','color'=>'teal',
             'label'=>__('90day.online_available'),
             'val'=>__('90day.anytime_anywhere')],
        ] as $stat)
        <div class="bg-white border border-slate-200 rounded-2xl p-4 text-center">
            <div class="w-10 h-10 bg-{{ $stat['color'] }}-100 rounded-xl flex items-center justify-center mx-auto mb-2">
                <i class="ti {{ $stat['icon'] }} text-{{ $stat['color'] }}-600 text-xl"></i>
            </div>
            <p class="text-xs text-slate-500 font-medium mb-1">{{ $stat['label'] }}</p>
            <p class="text-sm font-bold text-slate-800">{{ $stat['val'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ── Jump links ── --}}
    <div class="flex flex-wrap gap-2">
        @foreach([
            '#who-must'    => (__('90day.who_must_report')),
            '#how-to'      => (__('90day.steps_to_report')),
            '#online'      => (__('90day.report_online')),
            '#in-person'   => (__('90day.in_person')),
            '#by-mail'     => (__('90day.by_mail')),
            '#penalties'   => (__('90day.penalties')),
            '#faq'         => (__('90day.faq')),
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
            {{ __('90day.who_must_file_a_90_day_report') }}
        </h2>
        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-6">
            <p class="text-slate-700 text-sm leading-relaxed mb-5">
                {!! __('90day.under_section_37_5_of_the_html') !!}
            </p>
            <div class="grid sm:grid-cols-2 gap-3">
                @foreach([
                    ['icon'=>'ti-circle-check','color'=>'green',
                     'text'=>__('90day.long_stay_visa_holders_non_imm_b_ed'),
                     'desc'=>__('90day.includes_work_student_retirement')],
                    ['icon'=>'ti-circle-check','color'=>'green',
                     'text'=>__('90day.those_who_extended_their_stay'),
                     'desc'=>__('90day.even_after_extension_must_continue')],
                    ['icon'=>'ti-x','color'=>'red',
                     'text'=>__('90day.not_required_tourists_tourist_visa'),
                     'desc'=>__('90day.tourist_visas_are_typically_under')],
                    ['icon'=>'ti-x','color'=>'red',
                     'text'=>__('90day.not_required_thailand_elite_card'),
                     'desc'=>__('90day.thailand_elite_members_have_a')],
                ] as $who)
                <div class="flex items-start gap-3 bg-white rounded-xl border border-indigo-200 p-4">
                    <div class="w-8 h-8 bg-{{ $who['color'] }}-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                        <i class="ti {{ $who['icon'] }} text-{{ $who['color'] }}-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ $who['text'] }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $who['desc'] }}</p>
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
            {{ __('90day.how_to_complete_your_90_day_report') }}
        </h2>
        <p class="text-sm text-slate-500 mb-5 ml-10">
            {{ __('90day.choose_the_most_convenient_method') }}
        </p>
        <div class="space-y-3">
            @foreach([
                [
                    'n'=>1,'icon'=>'ti-calendar-stats','color'=>'indigo',
                    'text'=>__('90day.know_your_due_date'),
                    'detail'=>__('90day.count_90_days_from_1_your_last'),
                ],
                [
                    'n'=>2,'icon'=>'ti-files','color'=>'blue',
                    'text'=>__('90day.prepare_your_documents'),
                    'detail'=>__('90day.required_1_original_passport_with'),
                ],
                [
                    'n'=>3,'icon'=>'ti-route','color'=>'purple',
                    'text'=>__('90day.choose_your_reporting_method'),
                    'detail'=>__('90day.3_options_a_online_at_tm47'),
                ],
                [
                    'n'=>4,'icon'=>'ti-send','color'=>'green',
                    'text'=>__('90day.submit_and_receive_your_receipt'),
                    'detail'=>__('90day.after_submitting_you_receive_an'),
                ],
                [
                    'n'=>5,'icon'=>'ti-calendar-plus','color'=>'orange',
                    'text'=>__('90day.note_your_next_due_date'),
                    'detail'=>__('90day.your_next_due_date_is_printed_on'),
                ],
            ] as $step)
            <div class="flex gap-4 bg-white border border-slate-200 rounded-2xl p-5 hover:border-indigo-200 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-{{ $step['color'] }}-100 flex items-center justify-center shrink-0">
                    <i class="ti {{ $step['icon'] }} text-{{ $step['color'] }}-600 text-lg"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-{{ $step['color'] }}-600 uppercase tracking-wider">{{ __('90day.step') }} {{ $step['n'] }}</span>
                    <h3 class="font-semibold text-slate-800 text-sm mb-1 mt-0.5">
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

    {{-- ── SECTION 3: Three Methods ── --}}
    <section>
        <h2 class="text-xl font-bold text-slate-800 mb-5 flex items-center gap-2">
            <span class="w-8 h-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">3</span>
            {{ __('90day.3_ways_to_report_choose_what_s') }}
        </h2>

        {{-- Online --}}
        <div id="online" class="bg-indigo-50 border border-indigo-200 rounded-2xl p-6 mb-4">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shrink-0">
                    <i class="ti ti-device-laptop text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-indigo-900">
                        {{ __('90day.method_a_report_online_recommended') }}
                    </h3>
                    <p class="text-xs text-indigo-600">{{ __('90day.24_7_no_travel_needed') }}</p>
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-5">
                <ol class="space-y-2">
                    @foreach([
                        ['text'=>__('90day.go_to_tm47_immigration_go_th'), 'link' => true],
                        ['text'=>__('90day.register_or_log_in_first_time_users')],
                        ['text'=>__('90day.enter_your_passport_and_current')],
                        ['text'=>__('90day.review_details_and_confirm')],
                        ['text'=>__('90day.download_the_acknowledgement_slip')],
                    ] as $i => $s)
                    <li class="flex items-start gap-2 text-xs text-indigo-700">
                        <span class="w-5 h-5 bg-indigo-600 text-white rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">{{ $i+1 }}</span>
                        @if($s['link'] ?? false)
                            <span>
                                {{ __('90day.go_to') }}
                                <a href="https://tm47.immigration.go.th/tm47/#/login" target="_blank" rel="noopener" class="font-semibold text-indigo-800 underline hover:text-indigo-900">{{ __('90day.online_site') }}</a>
                            </span>
                        @else
                            {{ $s['text'] }}
                        @endif
                    </li>
                    @endforeach
                </ol>
                <div class="space-y-2">
                    <p class="text-xs font-semibold text-indigo-800 mb-2">{{ __('90day.benefits') }}</p>
                    @foreach([
                        ['icon'=>'ti-clock-24','text'=>__('90day.available_24_7_no_office_hours')],
                        ['icon'=>'ti-car-off','text'=>__('90day.no_travel_no_waiting_in_line')],
                        ['icon'=>'ti-certificate','text'=>__('90day.instant_digital_receipt')],
                        ['icon'=>'ti-history','text'=>__('90day.access_your_full_reporting_history')],
                    ] as $b)
                    <div class="flex items-center gap-2 bg-white rounded-lg p-2.5 border border-indigo-200">
                        <i class="ti {{ $b['icon'] }} text-indigo-600 shrink-0"></i>
                        <p class="text-xs text-slate-700">{{ $b['text'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <a href="https://tm47.immigration.go.th/tm47/#/login" target="_blank" rel="noopener"
               class="mt-5 inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-3 rounded-xl transition-colors">
                <i class="ti ti-device-laptop text-lg"></i>
                {{ __('90day.file_your_90_day_report_online_now') }}
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
                        {{ __('90day.method_b_in_person_at_immigration') }}
                    </h3>
                    <p class="text-xs text-slate-500">{{ __('90day.best_if_you_need_direct_assistance') }}</p>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-slate-700 mb-2">{{ __('90day.documents_to_bring') }}</p>
                    <ul class="space-y-1.5">
                        @foreach([
                            ['text'=>__('90day.original_passport_photocopy')],
                            ['text'=>__('90day.completed_tm47_form_available_at')],
                            ['text'=>__('90day.previous_acknowledgement_slip_if')],
                            ['text'=>__('90day.proof_of_address_lease_or_tm30')],
                        ] as $doc)
                        <li class="flex items-start gap-2 text-xs text-slate-600">
                            <i class="ti ti-circle-check text-blue-500 shrink-0 mt-0.5"></i>
                            {{ $doc['text'] }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold text-slate-700 mb-2">{{ __('90day.steps_at_the_office') }}</p>
                    <ol class="space-y-1.5">
                        @foreach([
                            ['text'=>__('90day.take_a_queue_number_from_the')],
                            ['text'=>__('90day.wait_to_be_called_submit_all')],
                            ['text'=>__('90day.officer_verifies_and_stamps_your')],
                            ['text'=>__('90day.receive_your_receipt_with_the_next')],
                        ] as $i => $s)
                        <li class="flex items-start gap-2 text-xs text-slate-600">
                            <span class="w-4 h-4 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">{{ $i+1 }}</span>
                            {{ $s['text'] }}
                        </li>
                        @endforeach
                    </ol>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-2.5 mt-3">
                        <p class="text-xs text-amber-700">
                            <i class="ti ti-clock mr-1"></i>
                            {{ __('90day.go_early_avoid_the_lunch_break_12') }}
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
                        {{ __('90day.method_c_registered_mail') }}
                    </h3>
                    <p class="text-xs text-slate-500">{{ __('90day.must_be_sent_at_least_7_days_before') }}</p>
                </div>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-semibold text-slate-700 mb-2">{{ __('90day.documents_to_send') }}</p>
                    <ul class="space-y-1.5">
                        @foreach([
                            ['text'=>__('90day.completed_tm47_form')],
                            ['text'=>__('90day.passport_copy_visa_page_latest')],
                            ['text'=>__('90day.copy_of_previous_acknowledgement')],
                            ['text'=>__('90day.self_addressed_stamped_envelope_for')],
                        ] as $doc)
                        <li class="flex items-start gap-2 text-xs text-slate-600">
                            <i class="ti ti-circle-check text-teal-500 shrink-0 mt-0.5"></i>
                            {{ $doc['text'] }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-xs font-semibold text-amber-800 mb-2">
                        <i class="ti ti-alert-triangle mr-1"></i>
                        {{ __('90day.important_cautions_mail_method') }}
                    </p>
                    <ul class="space-y-1.5 text-xs text-amber-700">
                        <li>{{ __('90day.use_registered_mail_not_regular') }}</li>
                        <li>{{ __('90day.send_at_least_7_days_early_to_allow') }}</li>
                        <li>{{ __('90day.keep_your_proof_of_postage_tracking') }}</li>
                        <li>{{ __('90day.not_recommended_for_first_timers') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ── SECTION 4: Timeline / Deadline Rules ── --}}
    <section>
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">4</span>
            {{ __('90day.key_deadline_rules') }}
        </h2>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach([
                ['icon'=>'ti-clock','color'=>'indigo','border'=>'border-indigo-200',
                 'title'=>__('90day.file_up_to_15_days_early'),
                 'desc'=>__('90day.you_can_file_your_90_day_report_up')],
                ['icon'=>'ti-plane-departure','color'=>'blue','border'=>'border-blue-200',
                 'title'=>__('90day.leaving_resets_the_90_day_count'),
                 'desc'=>__('90day.if_you_leave_and_re_enter_thailand')],
                ['icon'=>'ti-calendar-x','color'=>'red','border'=>'border-red-200',
                 'title'=>__('90day.filing_late_fine'),
                 'desc'=>__('90day.missing_the_deadline_and_filing')],
                ['icon'=>'ti-refresh','color'=>'teal','border'=>'border-teal-200',
                 'title'=>__('90day.visa_extension_reporting_exemption'),
                 'desc'=>__('90day.extending_your_visa_does_not_reset')],
                ['icon'=>'ti-file-check','color'=>'green','border'=>'border-green-200',
                 'title'=>__('90day.filing_visa_extension_file_both'),
                 'desc'=>__('90day.when_filing_a_visa_extension_if')],
                ['icon'=>'ti-passport','color'=>'orange','border'=>'border-orange-200',
                 'title'=>__('90day.new_passport_notify_immigration_too'),
                 'desc'=>__('90day.if_you_get_a_new_passport_bring')],
            ] as $rule)
            <div class="bg-white border {{ $rule['border'] }} rounded-2xl p-4">
                <div class="w-9 h-9 bg-{{ $rule['color'] }}-100 rounded-xl flex items-center justify-center mb-3">
                    <i class="ti {{ $rule['icon'] }} text-{{ $rule['color'] }}-600"></i>
                </div>
                <h3 class="font-bold text-slate-800 text-sm mb-1.5">{{ $rule['title'] }}</h3>
                <p class="text-xs text-slate-500 leading-relaxed">{{ $rule['desc'] }}</p>
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
                        {{ __('90day.penalties_for_not_reporting') }}
                    </h2>
                    <div class="grid sm:grid-cols-2 gap-3 mb-4">
                        @foreach([
                            ['icon'=>'ti-coin','color'=>'red',
                             'text'=>__('90day.fine_up_to_2_000'),
                             'desc'=>__('90day.for_late_or_missed_90_day_reports')],
                            ['icon'=>'ti-ban','color'=>'orange',
                             'text'=>__('90day.visa_extension_may_be_refused'),
                             'desc'=>__('90day.officers_may_refuse_your_extension')],
                            ['icon'=>'ti-alert-circle','color'=>'red',
                             'text'=>__('90day.possible_legal_proceedings'),
                             'desc'=>__('90day.repeated_violations_or_intentional')],
                            ['icon'=>'ti-plane-off','color'=>'purple',
                             'text'=>__('90day.may_affect_future_visa_applications'),
                             'desc'=>__('90day.a_history_of_non_compliance_may_be')],
                        ] as $p)
                        <div class="flex items-start gap-3 bg-white rounded-xl p-3 border border-red-200">
                            <i class="ti {{ $p['icon'] }} text-{{ $p['color'] }}-600 text-lg shrink-0 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $p['text'] }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $p['desc'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="bg-red-100 rounded-xl p-3 text-sm text-red-800 font-medium">
                        <i class="ti ti-info-circle mr-2"></i>
                        {{ __('90day.tip_set_a_calendar_reminder_for_75') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── SECTION 6: FAQ ── --}}
    <section id="faq">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-indigo-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">6</span>
            {{ __('90day.frequently_asked_questions') }}
        </h2>
        <div class="space-y-3" x-data="{open: null}">
            @foreach([
                [
                    'q'=>__('90day.q_i_ve_been_in_thailand_for_2_months'),
                    'a'=>__('90day.a_if_your_visa_or_permission_to_stay'),
                ],
                [
                    'q'=>__('90day.q_i_left_thailand_and_came_back_does'),
                    'a'=>__('90day.a_yes_every_time_you_re_enter'),
                ],
                [
                    'q'=>__('90day.q_i_missed_my_90_day_deadline_what'),
                    'a'=>__('90day.a_go_to_the_immigration_bureau_in'),
                ],
                [
                    'q'=>__('90day.q_my_visa_expires_in_2_weeks_but_my'),
                    'a'=>__('90day.a_if_you_are_extending_your_visa_tell'),
                ],
                [
                    'q'=>__('90day.q_can_someone_file_the_90_day_report'),
                    'a'=>__('90day.a_yes_the_representative_must_bring_a'),
                ],
            ] as $i => $faq)
            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                <button
                    @click="open = open === {{ $i }} ? null : {{ $i }}"
                    class="w-full text-left px-5 py-4 flex items-center justify-between gap-3 hover:bg-slate-50 transition-colors"
                >
                    <span class="font-medium text-slate-800 text-sm">{{ $faq['q'] }}</span>
                    <i class="ti ti-chevron-down text-slate-400 shrink-0 transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''"></i>
                </button>
                <div x-show="open === {{ $i }}" x-transition class="px-5 pb-4 text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-3">
                    {{ $faq['a'] }}
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
            {{ __('90day.download_forms') }}
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
        <div class="bg-indigo-600 rounded-2xl p-5 text-white flex items-center gap-4">
            <i class="ti ti-robot text-4xl opacity-80 shrink-0"></i>
            <div>
                <p class="font-bold mb-1">{{ __('90day.ask_the_ai_assistant') }}</p>
                <p class="text-indigo-100 text-xs mb-3">{{ __('90day.questions_about_the_90_day_report') }}</p>
                <span class="text-xs bg-white/20 rounded-lg px-3 py-1">{{ __('90day.available_24_7') }}</span>
            </div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-center gap-4">
            <i class="ti ti-phone text-green-600 text-4xl shrink-0"></i>
            <div>
                <p class="font-bold text-slate-800 mb-1">{{ __('90day.immigration_hotline') }}</p>
                @cmsEdit('setting:identity', 'สายด่วน', 'หมายเลขสายด่วน')<p class="text-3xl font-bold text-green-600 mb-1">{{ $hotline }}</p>
                <p class="text-xs text-slate-500">{{ __('90day.24_hours_a_day') }}</p>
            </div>
        </div>
    </div>

</div>
@endsection
