@extends('public.layouts.app')
@section('title', __('tm30.tm30_notification_guide_complete'))

@section('content')
@php $locale = app()->getLocale(); @endphp

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

    {{-- Breadcrumb --}}
    <nav class="text-xs text-slate-500 flex items-center gap-1.5">
        <a href="{{ route('public.home') }}" class="hover:text-teal-700">{{ __('tm30.home') }}</a>
        <span>/</span>
        <a href="{{ route('public.services.index') }}" class="hover:text-teal-700">{{ __('tm30.services') }}</a>
        <span>/</span>
        <span>{{ __('tm30.short_name') }}</span>
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
                        <span class="bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ __('tm30.form_badge') }}</span>
                    </div>
                    <h1 class="text-3xl font-bold">
                        {{ __('tm30.tm30_foreigner_accommodation') }}
                    </h1>
                    <p class="text-teal-200 text-sm mt-1">
                        {{ __('tm30.legal_obligation_for_accommodation') }}
                    </p>
                </div>
            </div>
            <p class="text-teal-100 leading-relaxed max-w-2xl">
                {!! __('tm30.thai_law_requires_any_accommodation_html') !!}
            </p>
        </div>
    </div>

    {{-- ── Critical Alert ── --}}
    <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-5 flex items-start gap-4">
        <i class="ti ti-alert-triangle text-amber-500 text-3xl shrink-0 mt-0.5"></i>
        <div>
            <h2 class="font-bold text-amber-900 mb-1">
                {{ __('tm30.important_who_must_file_tm30') }}
            </h2>
            <div class="grid sm:grid-cols-2 gap-3 mt-3">
                @foreach([
                    ['icon'=>'ti-building','color'=>'teal',
                     'text'=>__('tm30.hotels_guesthouses_hostels'),
                     'desc'=>__('tm30.every_time_a_foreign_guest_checks')],
                    ['icon'=>'ti-home','color'=>'blue',
                     'text'=>__('tm30.house_owners_landlords'),
                     'desc'=>__('tm30.when_renting_to_or_housing_a')],
                    ['icon'=>'ti-briefcase','color'=>'purple',
                     'text'=>__('tm30.employers_companies'),
                     'desc'=>__('tm30.when_foreign_employees_stay_in')],
                    ['icon'=>'ti-users','color'=>'orange',
                     'text'=>__('tm30.homeowners_hosting_foreign_family'),
                     'desc'=>__('tm30.even_thai_nationals_housing_a')],
                ] as $who)
                <div class="flex items-start gap-3 bg-white rounded-xl border border-amber-200 p-3">
                    <div class="w-9 h-9 bg-{{ $who['color'] }}-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="ti {{ $who['icon'] }} text-{{ $who['color'] }}-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ $who['text'] }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $who['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Jump links ── --}}
    <div class="flex flex-wrap gap-2">
        @foreach([
            '#what-is-tm30' => (__('tm30.what_is_tm30')),
            '#how-to-file'  => (__('tm30.how_to_file')),
            '#online'       => (__('tm30.file_online')),
            '#deadline'     => (__('tm30.deadline')),
            '#penalties'    => (__('tm30.penalties')),
            '#faq'          => (__('tm30.faq')),
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
            {{ __('tm30.what_is_tm30') }}
        </h2>
        <div class="bg-white border border-slate-200 rounded-2xl p-6 space-y-4">
            <p class="text-slate-700 leading-relaxed">
                {!! __('tm30.tm30_is_the_foreigner_accommodation_html') !!}
            </p>
            <div class="grid sm:grid-cols-3 gap-4">
                @foreach([
                    ['icon'=>'ti-clock','color'=>'red',
                     'label'=>__('tm30.deadline'),
                     'val'=>__('tm30.within_24_hours')],
                    ['icon'=>'ti-coin','color'=>'amber',
                     'label'=>__('tm30.fee'),
                     'val'=>__('tm30.free_of_charge')],
                    ['icon'=>'ti-device-laptop','color'=>'teal',
                     'label'=>__('tm30.method'),
                     'val'=>__('tm30.online_or_at_office')],
                ] as $stat)
                <div class="bg-{{ $stat['color'] }}-50 border border-{{ $stat['color'] }}-200 rounded-xl p-4 text-center">
                    <i class="ti {{ $stat['icon'] }} text-{{ $stat['color'] }}-600 text-2xl block mb-1"></i>
                    <p class="text-xs text-{{ $stat['color'] }}-600 font-semibold uppercase tracking-wide mb-1">{{ $stat['label'] }}</p>
                    <p class="text-sm font-bold text-slate-800">{{ $stat['val'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── SECTION 2: How to File (Step-by-step) ── --}}
    <section id="how-to-file">
        <h2 class="text-xl font-bold text-slate-800 mb-2 flex items-center gap-2">
            <span class="w-8 h-8 bg-teal-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">2</span>
            {{ __('tm30.how_to_file_tm30_step_by_step') }}
        </h2>
        <p class="text-sm text-slate-500 mb-5 ml-10">
            {{ __('tm30.can_be_done_online_or_at_the') }}
        </p>
        <div class="space-y-3">
            @foreach([
                [
                    'n'=>1,'icon'=>'ti-user-check','color'=>'teal',
                    'text'=>__('tm30.confirm_you_are_obligated_to_file'),
                    'detail'=>__('tm30.if_a_foreigner_stays_at_your'),
                ],
                [
                    'n'=>2,'icon'=>'ti-files','color'=>'blue',
                    'text'=>__('tm30.gather_the_required_information'),
                    'detail'=>__('tm30.information_needed_full_name'),
                ],
                [
                    'n'=>3,'icon'=>'ti-device-laptop','color'=>'purple',
                    'text'=>__('tm30.choose_filing_method_online_or_in'),
                    'detail'=>__('tm30.online_filing_at_tm30_immigration'),
                ],
                [
                    'n'=>4,'icon'=>'ti-forms','color'=>'orange',
                    'text'=>__('tm30.complete_the_tm30_form'),
                    'detail'=>__('tm30.complete_all_fields_accurately_your'),
                ],
                [
                    'n'=>5,'icon'=>'ti-send','color'=>'green',
                    'text'=>__('tm30.submit_the_form_and_receive_your'),
                    'detail'=>__('tm30.after_online_submission_the_system'),
                ],
                [
                    'n'=>6,'icon'=>'ti-archive','color'=>'slate',
                    'text'=>__('tm30.keep_the_receipt_as_proof'),
                    'detail'=>__('tm30.keep_both_digital_and_paper_copies'),
                ],
            ] as $step)
            <div class="flex gap-4 bg-white border border-slate-200 rounded-2xl p-5 hover:border-teal-200 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-{{ $step['color'] }}-100 flex items-center justify-center shrink-0">
                    <i class="ti {{ $step['icon'] }} text-{{ $step['color'] }}-600 text-lg"></i>
                </div>
                <div>
                    <span class="text-xs font-bold text-{{ $step['color'] }}-600 uppercase tracking-wider">{{ __('tm30.step') }} {{ $step['n'] }}</span>
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

    {{-- ── SECTION 3: Online Filing ── --}}
    <section id="online">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-teal-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">3</span>
            {{ __('tm30.filing_tm30_online_recommended') }}
        </h2>
        <div class="bg-teal-50 border border-teal-200 rounded-2xl p-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-semibold text-teal-800 mb-3 flex items-center gap-2 text-sm">
                        <i class="ti ti-device-laptop text-teal-600"></i>
                        {{ __('tm30.how_to_use_the_online_system') }}
                    </h3>
                    <ol class="space-y-2">
                        @foreach([
                            ['text'=>__('tm30.go_to_tm30_immigration_go_th'), 'link' => true],
                            ['text'=>__('tm30.register_a_user_account_first_time')],
                            ['text'=>__('tm30.log_in_and_select_report_foreigner')],
                            ['text'=>__('tm30.enter_passport_and_accommodation')],
                            ['text'=>__('tm30.review_and_submit_the_form')],
                            ['text'=>__('tm30.download_and_print_the_receipt')],
                        ] as $i => $step)
                        <li class="flex items-start gap-2 text-xs text-teal-700">
                            <span class="w-5 h-5 bg-teal-600 text-white rounded-full flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">{{ $i+1 }}</span>
                            @if($step['link'] ?? false)
                                <span>
                                    {{ __('tm30.go_to') }}
                                    <a href="https://tm30.immigration.go.th" target="_blank" rel="noopener" class="font-semibold text-teal-800 underline hover:text-teal-900">{{ __('tm30.online_site') }}</a>
                                </span>
                            @else
                                {{ $step['text'] }}
                            @endif
                        </li>
                        @endforeach
                    </ol>
                </div>
                <div class="space-y-3">
                    <h3 class="font-semibold text-teal-800 mb-3 flex items-center gap-2 text-sm">
                        <i class="ti ti-star text-teal-600"></i>
                        {{ __('tm30.benefits_of_online_filing') }}
                    </h3>
                    @foreach([
                        ['icon'=>'ti-clock-24','text'=>__('tm30.available_24_7_no_office_hours')],
                        ['icon'=>'ti-car-off','text'=>__('tm30.no_need_to_travel_to_the_office')],
                        ['icon'=>'ti-certificate','text'=>__('tm30.instant_receipt_pdf')],
                        ['icon'=>'ti-history','text'=>__('tm30.access_history_of_past')],
                    ] as $benefit)
                    <div class="flex items-center gap-3 bg-white rounded-xl p-3 border border-teal-200">
                        <i class="ti {{ $benefit['icon'] }} text-teal-600 text-lg shrink-0"></i>
                        <p class="text-xs text-slate-700">{{ $benefit['text'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>

            <a href="https://tm30.immigration.go.th" target="_blank" rel="noopener"
               class="mt-5 inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold px-5 py-3 rounded-xl transition-colors">
                <i class="ti ti-device-laptop text-lg"></i>
                {{ __('tm30.file_tm30_online_now') }}
                <i class="ti ti-external-link text-sm"></i>
            </a>
        </div>
    </section>

    {{-- ── SECTION 4: Deadline ── --}}
    <section id="deadline">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-teal-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">4</span>
            {{ __('tm30.deadlines_requirements') }}
        </h2>
        <div class="grid md:grid-cols-3 gap-4">
            @foreach([
                [
                    'icon'=>'ti-clock','color'=>'red','border'=>'border-red-300',
                    'title'=>__('tm30.within_24_hours_2'),
                    'desc'=>__('tm30.from_the_moment_the_foreigner'),
                ],
                [
                    'icon'=>'ti-refresh','color'=>'blue','border'=>'border-blue-200',
                    'title'=>__('tm30.file_every_time'),
                    'desc'=>__('tm30.not_just_the_first_time_every_time'),
                ],
                [
                    'icon'=>'ti-map-pin','color'=>'teal','border'=>'border-teal-200',
                    'title'=>__('tm30.where_to_report'),
                    'desc'=>__('tm30.online_at_tm30_immigration_go_th_or'),
                ],
            ] as $d)
            <div class="bg-white border {{ $d['border'] }} rounded-2xl p-5">
                <div class="w-10 h-10 bg-{{ $d['color'] }}-100 rounded-xl flex items-center justify-center mb-3">
                    <i class="ti {{ $d['icon'] }} text-{{ $d['color'] }}-600 text-xl"></i>
                </div>
                <h3 class="font-bold text-slate-800 mb-2 text-sm">{{ $d['title'] }}</h3>
                <p class="text-xs text-slate-500 leading-relaxed">{{ $d['desc'] }}</p>
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
                        {{ __('tm30.penalties_for_not_filing_tm30') }}
                    </h2>
                    <div class="grid sm:grid-cols-2 gap-3 mb-4">
                        @foreach([
                            ['icon'=>'ti-coin','color'=>'red',
                             'text'=>__('tm30.fine_up_to_2_000'),
                             'desc'=>__('tm30.for_accommodation_owners_who_fail')],
                            ['icon'=>'ti-ban','color'=>'orange',
                             'text'=>__('tm30.foreigner_may_be_denied_visa'),
                             'desc'=>__('tm30.without_a_tm30_receipt_officers_may')],
                            ['icon'=>'ti-alert-circle','color'=>'red',
                             'text'=>__('tm30.repeat_offenders_face_criminal'),
                             'desc'=>__('tm30.repeated_violations_may_lead_to')],
                            ['icon'=>'ti-shield-off','color'=>'purple',
                             'text'=>__('tm30.may_affect_business_license'),
                             'desc'=>__('tm30.hotels_and_businesses_that_do_not')],
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
                        {{ __('tm30.tip_file_tm30_immediately_when_the') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ── SECTION 6: FAQ ── --}}
    <section id="faq">
        <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 bg-teal-600 text-white rounded-lg flex items-center justify-center text-sm font-bold">6</span>
            {{ __('tm30.frequently_asked_questions') }}
        </h2>
        <div class="space-y-3" x-data="{open: null}">
            @foreach([
                [
                    'q'=>__('tm30.q_if_a_foreigner_stays_only_one_night'),
                    'a'=>__('tm30.a_yes_you_must_file_regardless_of'),
                ],
                [
                    'q'=>__('tm30.q_if_the_foreigner_leaves_and_comes'),
                    'a'=>__('tm30.a_yes_every_time_the_foreigner_leaves'),
                ],
                [
                    'q'=>__('tm30.q_does_the_foreigner_file_tm30_or'),
                    'a'=>__('tm30.a_the_accommodation_owner_or_occupant'),
                ],
                [
                    'q'=>__('tm30.q_if_a_foreign_friend_stays_at_my'),
                    'a'=>__('tm30.a_yes_even_private_homes_where'),
                ],
                [
                    'q'=>__('tm30.q_what_happens_if_i_file_late'),
                    'a'=>__('tm30.a_a_fine_of_up_to_2_000_applies_if'),
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
            <i class="ti ti-download text-teal-600"></i>
            {{ __('tm30.download_forms') }}
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
        <div class="bg-teal-600 rounded-2xl p-5 text-white flex items-center gap-4">
            <i class="ti ti-robot text-4xl opacity-80 shrink-0"></i>
            <div>
                <p class="font-bold mb-1">{{ __('tm30.ask_the_ai_assistant') }}</p>
                <p class="text-teal-100 text-xs mb-3">{{ __('tm30.questions_about_tm30_click_the_ai') }}</p>
                <span class="text-xs bg-white/20 rounded-lg px-3 py-1">{{ __('tm30.available_24_7') }}</span>
            </div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-center gap-4">
            <i class="ti ti-phone text-green-600 text-4xl shrink-0"></i>
            <div>
                <p class="font-bold text-slate-800 mb-1">{{ __('tm30.immigration_hotline') }}</p>
                @cmsEdit('setting:identity', 'สายด่วน', 'หมายเลขสายด่วน')<p class="text-3xl font-bold text-green-600 mb-1">{{ $hotline }}</p>
                <p class="text-xs text-slate-500">{{ __('tm30.24_hours_a_day') }}</p>
            </div>
        </div>
    </div>

</div>
@endsection
