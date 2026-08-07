<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — Internal Portal | ID5 @ Thailand 5.0</title>

    <link rel="stylesheet" href="{{ asset('vendor/tabler-icons/tabler-icons.min.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="portal-shell antialiased pattern-dots" x-data="{ moreOpen: false }">

    <div class="flex min-h-screen">

        {{-- Desktop sidebar --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:shrink-0 border-r border-white/10 bg-white/3 backdrop-blur-md">
            <div class="flex items-center gap-2 px-5 py-5 border-b border-white/10">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-auto" onerror="this.style.display='none'">
                <div class="leading-tight">
                    <span class="block font-bold text-white text-sm">ID5 @ Thailand 5.0</span>
                    <span class="block text-[11px] text-slate-400 uppercase tracking-widest">Internal Portal</span>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                @foreach (\App\Support\PortalNav::items() as $item)
                    @if ($item['type'] === 'link')
                        <a href="{{ route($item['route']) }}"
                           class="portal-nav-link {{ request()->routeIs($item['route']) ? 'portal-nav-link-active' : '' }}">
                            <i class="ti {{ $item['icon'] }} text-lg"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @else
                        @php($groupActive = \App\Support\PortalNav::isGroupActive($item))
                        <div x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }">
                            <button type="button" @click="open = !open"
                                    class="portal-nav-link w-full justify-between {{ $groupActive ? 'portal-nav-link-active' : '' }}">
                                <span class="flex items-center gap-3">
                                    <i class="ti {{ $item['icon'] }} text-lg"></i>
                                    <span>{{ $item['label'] }}</span>
                                </span>
                                <i class="ti ti-chevron-down text-sm transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="open" x-transition class="ml-6 mt-1 space-y-1 border-l border-white/10 pl-3">
                                @foreach ($item['children'] as $child)
                                    <a href="{{ route($child['route']) }}"
                                       class="portal-nav-sublink {{ request()->routeIs($child['route']) ? 'portal-nav-sublink-active' : '' }}">
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="px-3 py-4 border-t border-white/10">
                <form method="POST" action="{{ route('internal.logout') }}">
                    @csrf
                    <button type="submit" class="portal-nav-link w-full text-left">
                        <i class="ti ti-logout-2 text-lg"></i>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">

            {{-- Topbar --}}
            <header class="portal-topbar">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('images/logo.png') }}" alt="" class="h-8 w-auto lg:hidden" onerror="this.style.display='none'">
                    <h1 class="text-base sm:text-lg font-semibold text-white truncate">@yield('title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                    <button type="button" class="relative w-9 h-9 flex items-center justify-center rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:text-white hover:bg-white/10 transition-colors">
                        <i class="ti ti-bell text-lg"></i>
                    </button>

                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" @click="open = !open" class="flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-white/5 transition-colors">
                            <span class="w-8 h-8 rounded-full bg-gold-500 text-brand-900 flex items-center justify-center text-sm font-bold">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </span>
                            <span class="hidden sm:block text-sm font-medium text-slate-200">{{ auth()->user()->name }}</span>
                            <i class="ti ti-chevron-down text-sm text-slate-400 hidden sm:block"></i>
                        </button>

                        <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-48 portal-card p-2 z-50">
                            <div class="px-3 py-2 border-b border-white/10 mb-1">
                                <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ auth()->user()->position ?? auth()->user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('internal.logout') }}">
                                @csrf
                                <button type="submit" class="portal-nav-link w-full text-left text-sm">
                                    <i class="ti ti-logout-2"></i> Sign Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mx-4 sm:mx-6 mt-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm px-4 py-2.5">
                    <i class="ti ti-check mr-1"></i> {{ session('success') }}
                </div>
            @endif

            {{-- Main content --}}
            <main class="flex-1 px-4 sm:px-6 py-6 pb-24 lg:pb-6">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Mobile bottom tab bar --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 grid grid-cols-5 bg-white/5 border-t border-white/10 backdrop-blur-md">
        @php($mobileItems = \App\Support\PortalNav::mobileItems())
        @foreach ($mobileItems as $item)
            @if ($item['route'] === 'more')
                <button type="button" @click="moreOpen = true" class="portal-mobile-link">
                    <i class="ti {{ $item['icon'] }} text-xl"></i>
                    {{ $item['label'] }}
                </button>
            @else
                <a href="{{ route($item['route']) }}" class="portal-mobile-link {{ request()->routeIs($item['route']) ? 'portal-mobile-link-active' : '' }}">
                    <i class="ti {{ $item['icon'] }} text-xl"></i>
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </nav>

    {{-- Mobile "More" overflow sheet --}}
    <div x-show="moreOpen" x-cloak x-transition.opacity class="lg:hidden fixed inset-0 z-50 bg-black/60" @click="moreOpen = false"></div>
    <div x-show="moreOpen" x-cloak x-transition.opacity.scale.95
         class="lg:hidden fixed bottom-0 inset-x-0 z-50 portal-card rounded-b-none p-4 pb-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-white">More</h2>
            <button type="button" @click="moreOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-white">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div class="grid grid-cols-3 gap-3">
            @foreach (\App\Support\PortalNav::overflowItems() as $item)
                <a href="{{ route($item['route']) }}" class="portal-card portal-card-hover flex flex-col items-center gap-2 p-4 text-center">
                    <i class="ti {{ $item['icon'] }} text-2xl text-gold-500"></i>
                    <span class="text-xs text-slate-200">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    @stack('scripts')
</body>
</html>
