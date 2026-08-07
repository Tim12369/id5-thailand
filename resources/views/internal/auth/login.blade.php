@extends('internal.layouts.guest')

@section('title', 'Sign In')

@section('content')
    <h1 class="text-xl font-bold text-white mb-1">Sign In</h1>
    <p class="text-sm text-slate-400 mb-6">Access the Immigration Bureau 5 internal portal.</p>

    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm px-4 py-2.5">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm px-4 py-2.5">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('internal.login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="portal-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="portal-input" placeholder="name@id5.local">
        </div>

        <div>
            <label for="password" class="portal-label">Password</label>
            <input id="password" type="password" name="password" required
                   class="portal-input" placeholder="••••••••">
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-slate-300">
                <input type="checkbox" name="remember" class="rounded border-white/20 bg-white/5 text-gold-500 focus:ring-gold-500">
                Remember me
            </label>

            <a href="{{ route('internal.password.request') }}" class="text-gold-500 hover:text-gold-600">
                Forgot password?
            </a>
        </div>

        <button type="submit" class="btn-portal-primary w-full">
            <i class="ti ti-login-2"></i> Sign In
        </button>
    </form>
@endsection
