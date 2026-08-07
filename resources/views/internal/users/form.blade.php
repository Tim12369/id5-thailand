@extends('internal.layouts.app')

@section('title', $targetUser ? 'Edit User' : 'Create User')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">{{ $targetUser ? 'Edit User' : 'Create User' }}</h2>
        <p class="text-sm text-slate-400 mt-1">Portal accounts, role assignment, and access control.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST"
          action="{{ $targetUser ? route('internal.users.update', $targetUser) : route('internal.users.store') }}"
          class="portal-card p-5 max-w-3xl">
        @csrf
        @if ($targetUser)
            @method('PUT')
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="portal-label">Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" value="{{ old('name', $targetUser?->name) }}" class="portal-input" required>
            </div>
            <div>
                <label class="portal-label">Email <span class="text-red-400">*</span></label>
                <input type="email" name="email" value="{{ old('email', $targetUser?->email) }}" class="portal-input" required>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
            <div>
                <label class="portal-label">Employee ID</label>
                <input type="text" name="employee_id" value="{{ old('employee_id', $targetUser?->employee_id) }}" class="portal-input">
            </div>
            <div>
                <label class="portal-label">Position</label>
                <input type="text" name="position" value="{{ old('position', $targetUser?->position) }}" class="portal-input">
            </div>
            <div>
                <label class="portal-label">Department</label>
                <input type="text" name="department" value="{{ old('department', $targetUser?->department) }}" class="portal-input">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $targetUser?->phone) }}" class="portal-input">
            </div>
            <div>
                <label class="portal-label">Station</label>
                <select name="station_id" class="portal-input">
                    <option value="">&mdash; None &mdash;</option>
                    @foreach ($stations as $station)
                        <option value="{{ $station->id }}" @selected(old('station_id', $targetUser?->station_id) == $station->id)>
                            {{ $station->getLocalizedName() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
            <div>
                <label class="portal-label">
                    Password {{ $targetUser ? '' : ' *' }}
                </label>
                <input type="password" name="password" class="portal-input" autocomplete="new-password"
                       @if (! $targetUser) required @endif>
                @if ($targetUser)
                    <p class="text-xs text-slate-500 mt-1">Leave blank to keep the current password.</p>
                @endif
            </div>
        </div>

        <div class="mt-4">
            <label class="portal-label">Roles <span class="text-red-400">*</span></label>
            <div class="flex flex-wrap gap-3 mt-2">
                @foreach ($roles as $role)
                    <label class="flex items-center gap-2 text-sm text-slate-200 bg-white/5 border border-white/10 rounded-lg px-3 py-2">
                        <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                               @checked(in_array($role->id, old('role_ids', $targetUser?->roles->pluck('id')->all() ?? [])))>
                        {{ $role->name }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn-portal-primary">Save</button>
            <a href="{{ route('internal.users.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
