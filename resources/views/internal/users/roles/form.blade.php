@extends('internal.layouts.app')

@section('title', $role ? 'Edit Role' : 'Create Role')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">{{ $role ? 'Edit Role' : 'Create Role' }}</h2>
        <p class="text-sm text-slate-400 mt-1">Choose exactly which modules this role can view and manage.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST"
          action="{{ $role ? route('internal.roles.update', $role) : route('internal.roles.store') }}"
          class="portal-card p-5 max-w-3xl">
        @csrf
        @if ($role)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="portal-label">Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" value="{{ old('name', $role?->name) }}" class="portal-input" required>
            </div>
            <div>
                <label class="portal-label">Slug <span class="text-red-400">*</span></label>
                <input type="text" name="slug" value="{{ old('slug', $role?->slug) }}" placeholder="e.g. staff_officer" class="portal-input" required>
            </div>
        </div>

        <div class="mt-4">
            <label class="portal-label">Description</label>
            <textarea name="description" rows="2" class="portal-input">{{ old('description', $role?->description) }}</textarea>
        </div>

        <div class="mt-6">
            <label class="portal-label mb-2 block">Permissions</label>
            @php($currentPermissionIds = old('permission_ids', $role?->permissions->pluck('id')->all() ?? []))

            <div class="space-y-3">
                @foreach ($permissionsByModule as $module => $permissions)
                    <div class="rounded-xl border border-white/10 p-3">
                        <p class="text-xs font-semibold text-slate-300 uppercase tracking-wide mb-2">{{ $module }}</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($permissions as $permission)
                                <label class="flex items-center gap-2 text-sm text-slate-200">
                                    <input type="checkbox" name="permission_ids[]" value="{{ $permission->id }}"
                                           @checked(in_array($permission->id, $currentPermissionIds))>
                                    {{ $permission->name }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn-portal-primary">Save</button>
            <a href="{{ route('internal.roles.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
