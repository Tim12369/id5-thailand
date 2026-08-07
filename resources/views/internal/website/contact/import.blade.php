@extends('internal.layouts.app')

@section('title', 'Bulk Import Offices')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Bulk Import Offices</h2>
        <p class="text-sm text-slate-400 mt-1">Upload a CSV of manually-verified offices (compiled from official government sources). Rows are matched by office code — existing offices are updated, new ones are added, nothing is ever duplicated or deleted.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="portal-card p-5 max-w-2xl mb-5">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-2">Expected CSV columns</h3>
        <p class="text-xs text-slate-500 leading-relaxed mb-2">
            Required: <code class="text-slate-300">code, name_th</code>, plus <code class="text-slate-300">province, region</code> for brand-new offices.
        </p>
        <p class="text-xs text-slate-500 leading-relaxed">
            Optional: <code class="text-slate-300">name_en, address_th, address_en, phone, email, website, is_active</code>.
            Leave <code class="text-slate-300">lat, lng</code> out entirely — the address is located on the map automatically when a row has none. Only include them if you already have verified coordinates you want to use instead.
        </p>
    </div>

    <form method="POST" action="{{ route('internal.website-contact.import.store') }}" enctype="multipart/form-data" class="portal-card p-5 max-w-2xl">
        @csrf
        <label class="portal-label">CSV file <span class="text-red-400">*</span></label>
        <input type="file" name="file" accept=".csv,text/csv" class="portal-input" required>

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn-portal-primary">Import</button>
            <a href="{{ route('internal.website-contact.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
