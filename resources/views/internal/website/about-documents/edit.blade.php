@extends('internal.layouts.app')

@section('title', 'Edit Document — ' . $document->name_th)

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Edit Document</h2>
        <p class="text-sm text-slate-400 mt-1">{{ $document->name_th }}</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('internal.website-about-documents.update', $document) }}" enctype="multipart/form-data" class="portal-card p-5 max-w-2xl">
        @csrf
        @include('internal.website.about-documents.form', ['document' => $document])
    </form>
@endsection
