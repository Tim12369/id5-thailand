@extends('internal.layouts.guest')

@section('title', 'Reset Password')

@section('content')
    <h1 class="text-xl font-bold text-white mb-1">Reset Password</h1>
    <p class="text-sm text-slate-400 mb-6">Choose a new password for your account.</p>

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm px-4 py-2.5">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('internal.password.update') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="portal-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus
                   class="portal-input" placeholder="name@id5.local">
        </div>

        <div>
            <label for="password" class="portal-label">New Password</label>
            <input id="password" type="password" name="password" required
                   class="portal-input" placeholder="••••••••">
        </div>

        <div>
            <label for="password_confirmation" class="portal-label">Confirm New Password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                   class="portal-input" placeholder="••••••••">
        </div>

        <button type="submit" class="btn-portal-primary w-full">
            <i class="ti ti-lock-check"></i> Reset Password
        </button>
    </form>
@endsection
