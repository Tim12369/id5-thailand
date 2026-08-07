@extends('internal.layouts.app')

@section('title', 'Edit Office')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Edit Office</h2>
        <p class="text-sm text-slate-400 mt-1">{{ $station->name_th }} <span class="text-slate-500">({{ $station->code }})</span></p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('internal.website-contact.update', $station) }}" class="portal-card p-5 max-w-3xl">
        @csrf
        @method('PUT')
        @include('internal.website.contact.form', ['station' => $station])

        <div class="flex items-center gap-3 mt-6">
            <button type="submit" class="btn-portal-primary">Save</button>
            <a href="{{ route('internal.website-contact.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
