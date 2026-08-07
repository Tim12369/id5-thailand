@extends('internal.layouts.app')

@section('title', 'Smart Guide Content')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Smart Guide Content</h2>
        <p class="text-sm text-slate-400 mt-1">Pick a persona × topic cell to edit its Who/What/Why/Where/When/How content. Empty cells have no content yet on the public site.</p>
    </div>

    <div class="portal-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-white/10">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">Persona</th>
                    @foreach ($topics as $topic)
                        <th class="text-center px-3 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wide">{{ $topic }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach ($personas as $persona)
                    <tr>
                        <td class="px-4 py-2.5 text-slate-200 font-medium whitespace-nowrap">{{ $persona }}</td>
                        @foreach ($topics as $topic)
                            @php $count = $filledCounts->get("{$persona}|{$topic}", 0); @endphp
                            <td class="text-center px-3 py-2.5">
                                <a href="{{ route('internal.smart-guide-content.edit', [$persona, $topic]) }}"
                                   class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-xs font-semibold transition-colors
                                          {{ $count > 0 ? 'bg-emerald-500/15 text-emerald-300 hover:bg-emerald-500/25' : 'bg-white/5 text-slate-500 hover:bg-white/10' }}">
                                    {{ $count > 0 ? $count . '/6' : '—' }}
                                </a>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <a href="{{ route('internal.smart-guide-topics.index') }}" class="text-sm text-brand-400 hover:text-brand-300">
            <i class="ti ti-arrow-left"></i> Back to Topics (icons, documents, steps, locations)
        </a>
    </div>
@endsection
