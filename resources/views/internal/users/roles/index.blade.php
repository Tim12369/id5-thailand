@extends('internal.layouts.app')

@section('title', 'Roles')

@section('content')
    @php($canManage = auth()->user()->hasPermission('users.manage'))

    <div class="flex items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-white">Roles</h2>
            <p class="text-sm text-slate-400 mt-1">Roles bundle permissions and are assigned to users.</p>
        </div>
        @if ($canManage)
            <a href="{{ route('internal.roles.create') }}" class="btn-portal-primary">
                <i class="ti ti-plus"></i> New Role
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
            @foreach ($roles as $role)
                <div class="flex items-center gap-4 p-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-100">{{ $role->name }}</p>
                        <p class="text-xs text-slate-400">{{ $role->description }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="badge-info">{{ $role->permissions_count }} permissions</span>
                            <span class="badge-neutral">{{ $role->users_count }} users</span>
                        </div>
                    </div>

                    @if ($canManage)
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('internal.roles.edit', $role) }}" class="btn-secondary btn-sm">
                                <i class="ti ti-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('internal.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger-ghost btn-sm">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection
