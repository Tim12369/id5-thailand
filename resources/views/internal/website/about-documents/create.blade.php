@extends('internal.layouts.app')

@section('title', 'Add About Us Document')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Add About Us Document</h2>
        <p class="text-sm text-slate-400 mt-1">Upload a PDF and it will appear in the Documents box on the public About Us page.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('internal.website-about-documents.store') }}" enctype="multipart/form-data" class="portal-card p-5 max-w-2xl">
        @csrf
        @include('internal.website.about-documents.form', ['document' => null])
    </form>
@endsection
