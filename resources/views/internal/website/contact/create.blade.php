@extends('internal.layouts.app')

@section('title', 'Add Office')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Add Office</h2>
        <p class="text-sm text-slate-400 mt-1">Add a newly-opened official immigration office. Use only verified information from official sources.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('internal.website-contact.store') }}" class="portal-card p-5 max-w-3xl">
        @csrf
        @include('internal.website.contact.form', ['station' => null])

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn-portal-primary">Add Office</button>
            <a href="{{ route('internal.website-contact.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
