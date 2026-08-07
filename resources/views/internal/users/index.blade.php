@extends('internal.layouts.app')

@section('title', 'Users')

@section('content')
    @php($canManage = auth()->user()->hasPermission('users.manage'))

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-white">Users</h2>
            <p class="text-sm text-slate-400 mt-1">Manage internal portal accounts, roles, and access.</p>
        </div>
        @if ($canManage)
            <a href="{{ route('internal.users.create') }}" class="btn-portal-primary">
                <i class="ti ti-plus"></i> New User
            </a>
        @endif
    </div>

    @if (session('success'))
        <div class="badge-success inline-flex mb-4"><i class="ti ti-check mr-1"></i> {{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="portal-card overflow-hidden">
        <div class="divide-y divide-white/5">
            @forelse ($users as $user)
                <div class="flex items-center gap-4 p-4">
                    <span class="w-10 h-10 rounded-full bg-gold-500 text-brand-900 flex items-center justify-center text-sm font-bold shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-100 truncate">{{ $user->name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $user->email }} @if ($user->position) &middot; {{ $user->position }} @endif</p>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            @foreach ($user->roles as $role)
                                <span class="badge-info">{{ $role->name }}</span>
                            @endforeach
                            @if ($user->station)
                                <span class="badge-neutral">{{ $user->station->getLocalizedName() }}</span>
                            @endif
                            @unless ($user->is_active)
                                <span class="badge-danger">Disabled</span>
                            @endunless
                        </div>
                    </div>

                    @if ($canManage)
                        <div class="flex items-center gap-2 shrink-0">
                            <form method="POST" action="{{ route('internal.users.toggle-active', $user) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn-secondary btn-sm" @if ($user->id === auth()->id()) disabled @endif>
                                    {{ $user->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            <a href="{{ route('internal.users.edit', $user) }}" class="btn-secondary btn-sm">
                                <i class="ti ti-edit"></i>
                            </a>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-400">No users yet.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
