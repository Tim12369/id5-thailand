@extends('internal.layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <h1 class="text-xl font-bold text-white mb-1">Forgot Password</h1>
    <p class="text-sm text-slate-400 mb-6">Enter your email and we'll send you a password reset link.</p>

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

    <form method="POST" action="{{ route('internal.password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="portal-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   class="portal-input" placeholder="name@id5.local">
        </div>

        <button type="submit" class="btn-portal-primary w-full">
            <i class="ti ti-send"></i> Send Reset Link
        </button>
    </form>

    <p class="text-center text-sm text-slate-400 mt-6">
        <a href="{{ route('internal.login') }}" class="text-gold-500 hover:text-gold-600">
            <i class="ti ti-arrow-left"></i> Back to Sign In
        </a>
    </p>
@endsection
