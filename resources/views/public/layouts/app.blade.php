<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO --}}
    <title>@yield('title', config('app.name', 'ID5 @ Thailand 5.0')) — {{ __('layout.title_suffix') }}</title>
    <meta name="description" content="@yield('meta_description', __('layout.meta_description'))">
    <meta property="og:title" content="@yield('title', 'ID5 @ Thailand 5.0')">
    <meta property="og:description" content="@yield('meta_description', '')">
    <meta property="og:type" content="website">
    @hasSection('og_image')
    <meta property="og:image" content="@yield('og_image')">
    @endif

    {{-- Assets --}}
    <link rel="stylesheet" href="{{ asset('vendor/tabler-icons/tabler-icons.min.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Page-level extra head --}}
    @stack('head')
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

    {{-- Skip to main (accessibility) --}}
    <a href="#main-content" class="skip-link">
        {{ __('layout.skip_to_content') }}
    </a>

    {{-- Navigation --}}
    @include('public.layouts.header')

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="bg-green-50 border-b border-green-200 text-green-800 text-sm text-center py-2 px-4">
            <i class="ti ti-check mr-1"></i> {{ session('success') }}
        </div>
    @endif

    {{-- Main Content --}}
    <main id="main-content">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('public.layouts.footer')

    {{-- Cookie consent banner (PDPA) --}}
    @include('components.public.cookie-banner')

    {{-- AI Immigration Assistant Widget --}}
    @include('public.layouts.partials.ai-widget')

    {{-- Page-level scripts --}}
    @stack('scripts')

    {{-- Edit mode: top bar + pencils, for website editors only (prints nothing otherwise) --}}
    @include('public.layouts.partials.cms-edit')

</body>
</html>
