@extends('internal.layouts.app')

@section('title', 'Permissions')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Permissions</h2>
        <p class="text-sm text-slate-400 mt-1">
            Permissions are defined per module in code and granted to roles.
            To change what a role can do, edit the role instead.
        </p>
    </div>

    <div class="space-y-3">
        @foreach ($permissionsByModule as $module => $permissions)
            <div class="portal-card p-4">
                <p class="text-xs font-semibold text-slate-300 uppercase tracking-wide mb-3">{{ $module }}</p>
                <div class="divide-y divide-white/5">
                    @foreach ($permissions as $permission)
                        <div class="flex items-center justify-between gap-4 py-2">
                            <div>
                                <p class="text-sm text-slate-100">{{ $permission->name }}</p>
                                <p class="text-xs text-slate-500">{{ $permission->slug }}</p>
                            </div>
                            <div class="flex flex-wrap gap-1 justify-end">
                                @forelse ($permission->roles as $role)
                                    <span class="badge-info">{{ $role->name }}</span>
                                @empty
                                    <span class="badge-neutral">No roles</span>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        <a href="{{ route('internal.roles.index') }}" class="btn-secondary">
            <i class="ti ti-arrow-left"></i> Back to Roles
        </a>
    </div>
@endsection
