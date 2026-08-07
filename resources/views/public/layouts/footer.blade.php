@php
    // Footer copyright/address text and any extra "Quick Links" are
    // admin-editable via Internal Portal > Website Management > Footer.
    // The footer's four curated link columns below are left untouched --
    // they're a hand-tuned layout, not a flat list, so admin-added links
    // appear as an additional row instead of trying to slot into them.
    $footerCopyright = \App\Models\Setting::get('site.footer_copyright');
    $footerAddress = \App\Models\Setting::get('site.footer_address');
    $customFooterItems = \App\Models\MenuItem::location(\App\Models\MenuItem::LOCATION_FOOTER)->visible()->get();
@endphp
<footer class="bg-blue-950 text-blue-200 mt-20" role="contentinfo">

    {{-- Emergency strap — always reachable, even at the bottom of long pages --}}
    <div class="border-b border-white/10 bg-blue-900/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="flex items-center gap-2.5 text-sm text-blue-100">
                <span class="icon-tile w-9 h-9 bg-red-500/15 text-red-300"><i class="ti ti-phone-call text-base"></i></span>
                {{ __('layout.footer_emergency_lead') }}
            </p>
            <a href="tel:1178" class="btn-danger btn-sm">
                <i class="ti ti-phone"></i> {{ __('layout.hotline_strap') }} 1178
            </a>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10">

            {{-- Brand column --}}
            <div class="lg:col-span-4">
                <div class="flex items-center gap-3 mb-4">
                    <img src="{{ asset('images/logo-white.png') }}" alt="" class="h-11 w-auto" onerror="this.style.display='none'">
                    <div>
                        <p class="font-bold text-white text-[15px]">{{ $siteName ?? 'ID5 @ Thailand 5.0' }}</p>
                        <p class="text-xs text-blue-300">{{ __('layout.brand_tagline') }}</p>
                    </div>
                </div>
                <p class="text-sm text-blue-300 leading-relaxed max-w-sm">
                    {{ __('footer.description') }}
                </p>

                <div class="flex items-center gap-3 mt-5">
                    <span class="badge bg-white/5 text-blue-200 border border-white/10">
                        <i class="ti ti-language"></i> TH · EN
                    </span>
                    <span class="badge bg-white/5 text-blue-200 border border-white/10">
                        <i class="ti ti-accessible"></i> WCAG 2.1 AA
                    </span>
                </div>

                <p class="text-xs text-blue-400 mt-5 pt-5 border-t border-white/10">
                    {{ __('footer.tagline') }}
                </p>
            </div>

            {{-- Link columns --}}
            <div class="lg:col-span-2">
                <h3 class="text-white font-semibold text-sm mb-4">{{ __('footer.services') }}</h3>
                <ul class="space-y-2.5">
                    @foreach ([
                        ['route' => 'public.services.visa',      'label' => __('nav.visa')],
                        ['route' => 'public.services.tm30',      'label' => 'TM30'],
                        ['route' => 'public.services.90day',     'label' => __('nav.90day')],
                        ['route' => 'public.services.extension', 'label' => __('nav.extension')],
                        ['route' => 'public.services.overstay',  'label' => __('nav.overstay')],
                        ['route' => 'public.downloads.index',    'label' => __('nav.downloads')],
                    ] as $link)
                    <li>
                        <a href="{{ route($link['route']) }}" class="focus-ring text-sm text-blue-300 hover:text-white transition-colors">
                            {{ $link['label'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            <div class="lg:col-span-2">
                <h3 class="text-white font-semibold text-sm mb-4">{{ __('footer.resources') }}</h3>
                <ul class="space-y-2.5">
                    @foreach ([
                        ['route' => 'public.smart-guide.index','label' => __('nav.smart_guide')],
                        ['route' => 'public.knowledge.index',  'label' => __('nav.knowledge')],
                        ['route' => 'public.faq',              'label' => __('nav.faq')],
                        ['route' => 'public.news.index',       'label' => __('nav.news')],
                        ['route' => 'public.ita',              'label' => 'ITA'],
                        ['route' => 'public.open-data',        'label' => __('nav.open_data')],
                    ] as $link)
                    <li>
                        <a href="{{ route($link['route']) }}" class="focus-ring text-sm text-blue-300 hover:text-white transition-colors">
                            {{ $link['label'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            <div class="lg:col-span-2">
                <h3 class="text-white font-semibold text-sm mb-4">{{ __('footer.about') }}</h3>
                <ul class="space-y-2.5">
                    @foreach ([
                        ['route' => 'public.about',       'label' => __('footer.about_link')],
                        ['route' => 'public.ecosystem',   'label' => __('footer.ecosystem_link')],
                        ['route' => 'public.contact',     'label' => __('nav.contact')],
                        ['route' => 'public.alerts',      'label' => __('footer.alerts_link')],
                        ['route' => 'public.announcements.index', 'label' => __('footer.announcements_link')],
                    ] as $link)
                    <li>
                        <a href="{{ route($link['route']) }}" class="focus-ring text-sm text-blue-300 hover:text-white transition-colors">
                            {{ $link['label'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Contact column --}}
            <div class="lg:col-span-2">
                <h3 class="text-white font-semibold text-sm mb-4">{{ __('footer.contact') }}</h3>
                <ul class="space-y-3 text-sm text-blue-300">
                    <li class="flex items-start gap-2.5">
                        <i class="ti ti-phone mt-0.5 shrink-0 text-blue-400"></i>
                        <span>{{ __('layout.hotline_strap') }} <strong class="text-white font-semibold">1178</strong></span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <i class="ti ti-clock mt-0.5 shrink-0 text-blue-400"></i>
                        <span>{{ __('footer.working_hours') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <i class="ti ti-map-pin mt-0.5 shrink-0 text-blue-400"></i>
                        <span>{{ $footerAddress ?: __('footer.region') }}</span>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <i class="ti ti-mail mt-0.5 shrink-0 text-blue-400"></i>
                        <a href="{{ route('public.contact') }}" class="focus-ring hover:text-white transition-colors">{{ __('footer.contact_us') }}</a>
                    </li>
                </ul>
            </div>
        </div>

        @if ($customFooterItems->isNotEmpty())
            {{-- Quick Links — admin-added, appended below the curated columns above --}}
            <div class="border-t border-white/10 mt-10 pt-6">
                <h3 class="text-white font-semibold text-sm mb-3">Quick Links</h3>
                <div class="flex flex-wrap gap-x-5 gap-y-2">
                    @foreach ($customFooterItems as $item)
                        <a href="{{ str_starts_with($item->url, 'http') ? $item->url : url($item->url) }}"
                           class="focus-ring text-sm text-blue-300 hover:text-white transition-colors">
                            {{ $item->getLocalizedLabel() }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Bottom bar --}}
        <div class="border-t border-white/10 mt-10 pt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
            <p class="text-xs text-blue-400 text-center sm:text-left">
                {{ $footerCopyright ?: '© ' . date('Y') . ' ' . __('footer.org_name') . '. ' . __('footer.rights') }}
            </p>
            <div class="flex flex-wrap justify-center gap-x-5 gap-y-2 text-xs text-blue-400">
                <a href="{{ route('public.about') }}" class="focus-ring hover:text-white transition-colors">{{ __('footer.privacy') }}</a>
                <a href="{{ route('public.about') }}" class="focus-ring hover:text-white transition-colors">{{ __('footer.terms') }}</a>
                <a href="{{ route('public.about') }}" class="focus-ring hover:text-white transition-colors">{{ __('footer.accessibility_link') }}</a>
                <a href="{{ route('public.ita') }}" class="focus-ring hover:text-white transition-colors">{{ __('footer.ita_link') }}</a>
                <a href="{{ route('public.open-data') }}" class="focus-ring hover:text-white transition-colors">{{ __('footer.opendata_link') }}</a>
            </div>
        </div>
    </div>
</footer>
