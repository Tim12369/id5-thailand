<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Internal Portal') — ID5 @ Thailand 5.0</title>

    <link rel="stylesheet" href="{{ asset('vendor/tabler-icons/tabler-icons.min.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal-shell antialiased pattern-dots">

    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">

            <div class="flex flex-col items-center gap-2 mb-8 text-center">
                <img src="{{ asset('images/logo.png') }}" alt="" class="h-12 w-auto" onerror="this.style.display='none'">
                <span class="block font-bold text-white text-lg tracking-tight">ID5 @ Thailand 5.0</span>
                <span class="block text-xs text-slate-400 uppercase tracking-widest">Internal Portal</span>
            </div>

            <div class="portal-card p-6 sm:p-8">
                @yield('content')
            </div>

            <p class="text-center text-xs text-slate-500 mt-6">
                &copy; {{ date('Y') }} {{ __('Immigration Bureau 5') }} — {{ __('Internal use only') }}
            </p>
        </div>
    </div>

</body>
</html>
