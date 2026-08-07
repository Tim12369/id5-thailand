@extends('internal.layouts.app')

@section('title', 'Languages')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Languages</h2>
        <p class="text-sm text-slate-400 mt-1">
            Locales currently supported by the public site. Adding a new language requires a
            developer to add translation files, so this is a status report rather than an editor.
        </p>
    </div>

    <div class="portal-card overflow-hidden">
        <div class="divide-y divide-white/5">
            @foreach ($coverage as $code => $info)
                <div class="flex items-center gap-4 p-4">
                    <span class="badge-info shrink-0 uppercase">{{ $code }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-slate-100">{{ $info['label'] }}</p>
                        <p class="text-xs text-slate-500">{{ $info['file_count'] }} translation files</p>
                    </div>
                    @if ($info['is_default'])
                        <span class="badge-success">Default</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection
