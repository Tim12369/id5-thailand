{{-- ════════════════════════════════════════════════════════════════
     Public navigation — utility strap + sticky primary header
     Built with Alpine.js for accessible, keyboard-friendly menus
     (dropdowns use aria-expanded / aria-haspopup / focus management)
     ════════════════════════════════════════════════════════════════ --}}

@php
    // Custom header menu items come from WordPress (Appearance > Menus,
    // assigned to the "เมนูด้านบน" location) and are synced into menu_items
    // by App\Services\Cms\CmsSyncService. When no WordPress menu has been
    // assigned, the header keeps its current hand-tuned nav (active-state
    // highlighting, icons, emergency button styling) exactly as before --
    // this only takes over once an admin has actually built a menu, so the
    // site can never end up with no navigation.
    $customHeaderItems = \App\Models\MenuItem::location(\App\Models\MenuItem::LOCATION_HEADER)->visible()->get();
@endphp
<header class="bg-white/95 backdrop-blur-sm border-b border-slate-200 sticky top-0 z-40 shadow-[0_1px_0_rgba(15,23,42,0.04)]"
        x-data="{ mobileOpen: false }"
        @keydown.escape.window="mobileOpen = false">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-17 gap-4">

            {{-- Logo / Brand --}}
            @cmsEdit('setting:identity', 'ชื่อเว็บไซต์', 'ชื่อเว็บไซต์และสายด่วน')
            <a href="{{ route('public.home') }}" class="focus-ring flex items-center gap-3 shrink-0 -ml-1 px-1 py-1">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-10 w-auto" onerror="this.style.display='none'">
                <span class="block leading-tight">
                    <span class="block font-bold text-blue-900 text-[15px] tracking-tight">{{ $siteName ?? 'ID5 @ Thailand 5.0' }}</span>
                    <span class="block text-[11px] text-slate-500">{{ __('layout.brand_tagline') }}</span>
                </span>
            </a>

            {{-- Desktop nav — flattened single row --}}
            @cmsEdit($customHeaderItems->isNotEmpty() ? 'menus' : null, 'เมนูนำทาง', 'เมนูด้านบน')<nav class="hidden lg:flex items-center gap-0.5" aria-label="{{ __('layout.main_nav') }}">
                @if ($customHeaderItems->isNotEmpty())
                    @foreach ($customHeaderItems as $item)
                        <a href="{{ str_starts_with($item->url, 'http') ? $item->url : url($item->url) }}"
                           class="nav-link {{ request()->is(ltrim($item->url, '/')) ? 'nav-link-active' : '' }}">
                            {{ $item->getLocalizedLabel() }}
                        </a>
                    @endforeach
                @else
                    <a href="{{ route('public.home') }}"
                       class="nav-link {{ request()->routeIs('public.home') ? 'nav-link-active' : '' }}">
                        {{ __('nav.home') }}
                    </a>

                    <a href="{{ route('public.about') }}"
                       class="nav-link {{ request()->routeIs('public.about') ? 'nav-link-active' : '' }}">
                        {{ __('nav.about') }}
                    </a>

                    <a href="{{ route('public.ita') }}"
                       class="nav-link {{ request()->routeIs('public.ita') ? 'nav-link-active' : '' }}">
                        {{ __('nav.ita') }}
                    </a>

                    <a href="{{ route('public.news.index') }}"
                       class="nav-link {{ request()->routeIs('public.news.*', 'public.announcements.*', 'public.alerts') ? 'nav-link-active' : '' }}">
                        {{ __('nav.news') }}
                    </a>

                    <a href="{{ route('public.services.index') }}"
                       class="nav-link {{ request()->routeIs('public.services.*') ? 'nav-link-active' : '' }}">
                        {{ __('nav.service_center') }}
                    </a>

                    <a href="{{ route('public.contact') }}"
                       class="nav-link {{ request()->routeIs('public.contact') ? 'nav-link-active' : '' }}">
                        {{ __('nav.contact') }}
                    </a>
                @endif
            </nav>

            {{-- Right side: Search + Emergency + Mobile toggle --}}
            <div class="flex items-center gap-2 shrink-0">

                {{-- Search --}}
                <a href="{{ route('public.search') }}"
                   class="focus-ring hidden md:flex items-center gap-2.5 text-slate-400 hover:text-blue-700
                          bg-slate-100/80 hover:bg-blue-50 rounded-xl pl-3.5 pr-3 py-2 text-sm transition-colors w-44 lg:w-56">
                    <i class="ti ti-search text-base"></i>
                    <span class="text-slate-400 text-xs">{{ __('layout.search_placeholder') }}</span>
                </a>
                <a href="{{ route('public.search') }}" aria-label="{{ __('nav.search') }}"
                   class="focus-ring md:hidden p-2.5 text-slate-500 hover:text-blue-700 hover:bg-slate-100 rounded-xl">
                    <i class="ti ti-search text-xl"></i>
                </a>

                {{-- Language switcher --}}
                <div class="hidden lg:flex items-center gap-1 text-xs">
                    @foreach (['th' => __('layout.lang_th_short'), 'en' => __('layout.lang_en_short')] as $code => $label)
                    <a href="{{ route('public.lang.switch', $code) }}"
                       class="focus-ring px-2 py-1 rounded-lg {{ app()->getLocale() === $code ? 'text-blue-700 font-semibold bg-blue-50' : 'text-slate-500 hover:text-blue-700 hover:bg-slate-100' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>

                {{-- Emergency button --}}
                <a href="{{ route('public.emergency') }}"
                   class="hidden sm:inline-flex items-center gap-1.5 bg-red-600 hover:bg-red-700
                          text-white text-xs font-semibold px-3.5 py-2.5 rounded-xl transition-colors shadow-sm shadow-red-900/15">
                    <i class="ti ti-phone text-sm"></i>
                    {{ __('nav.emergency') }}
                </a>

                {{-- Mobile hamburger --}}
                <button type="button"
                        class="focus-ring lg:hidden p-2.5 text-slate-600 hover:text-blue-700 hover:bg-slate-100 rounded-xl"
                        :aria-expanded="mobileOpen" aria-controls="mobile-menu"
                        :aria-label="mobileOpen ? @jsText(__('layout.close_menu')) : @jsText(__('layout.open_menu'))"
                        @click="mobileOpen = !mobileOpen">
                    <i class="ti text-xl" :class="mobileOpen ? 'ti-x' : 'ti-menu-2'"></i>
                </button>

            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div id="mobile-menu" x-show="mobileOpen" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         class="lg:hidden border-t border-slate-200 bg-white max-h-[calc(100vh-68px)] overflow-y-auto"
         :aria-hidden="!mobileOpen">
        @cmsEdit($customHeaderItems->isNotEmpty() ? 'menus' : null, 'เมนูนำทาง', 'เมนูด้านบน')<div class="max-w-7xl mx-auto px-4 py-4 space-y-1">
            @if ($customHeaderItems->isNotEmpty())
                @foreach ($customHeaderItems as $item)
                    <a href="{{ str_starts_with($item->url, 'http') ? $item->url : url($item->url) }}"
                       class="mobile-link {{ request()->is(ltrim($item->url, '/')) ? 'mobile-link-active' : '' }}">
                        {{ $item->getLocalizedLabel() }} <i class="ti ti-chevron-right text-slate-300"></i>
                    </a>
                @endforeach
            @else
                <a href="{{ route('public.home') }}" class="mobile-link {{ request()->routeIs('public.home') ? 'mobile-link-active' : '' }}">
                    {{ __('nav.home') }} <i class="ti ti-chevron-right text-slate-300"></i>
                </a>
                <a href="{{ route('public.about') }}" class="mobile-link {{ request()->routeIs('public.about') ? 'mobile-link-active' : '' }}">
                    {{ __('nav.about') }} <i class="ti ti-chevron-right text-slate-300"></i>
                </a>
                <a href="{{ route('public.ita') }}" class="mobile-link {{ request()->routeIs('public.ita') ? 'mobile-link-active' : '' }}">
                    {{ __('nav.ita') }} <i class="ti ti-chevron-right text-slate-300"></i>
                </a>
                <a href="{{ route('public.news.index') }}" class="mobile-link {{ request()->routeIs('public.news.*') ? 'mobile-link-active' : '' }}">
                    {{ __('nav.news') }} <i class="ti ti-chevron-right text-slate-300"></i>
                </a>
                <a href="{{ route('public.services.index') }}" class="mobile-link {{ request()->routeIs('public.services.*') ? 'mobile-link-active' : '' }}">
                    {{ __('nav.service_center') }} <i class="ti ti-chevron-right text-slate-300"></i>
                </a>
                <a href="{{ route('public.contact') }}" class="mobile-link {{ request()->routeIs('public.contact') ? 'mobile-link-active' : '' }}">
                    {{ __('nav.contact') }} <i class="ti ti-chevron-right text-slate-300"></i>
                </a>
            @endif

            {{-- Language switcher --}}
            <div class="pt-3 mt-2 border-t border-slate-100">
                <p class="px-4 pb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('layout.language') }}</p>
                <div class="flex gap-2 px-4">
                    @foreach (['th' => __('layout.lang_th'), 'en' => __('layout.lang_en')] as $code => $label)
                    <a href="{{ route('public.lang.switch', $code) }}"
                       class="focus-ring flex-1 text-center text-xs font-medium py-2.5 rounded-xl border
                              {{ app()->getLocale() === $code ? 'bg-blue-50 border-blue-300 text-blue-700' : 'border-slate-200 text-slate-600 hover:border-blue-200' }}">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('public.emergency') }}"
               class="btn-danger w-full mt-3">
                <i class="ti ti-phone"></i> {{ __('nav.emergency') }} · {{ __('layout.hotline_strap') }} {{ $hotline }}
            </a>
        </div>
    </div>
</header>
