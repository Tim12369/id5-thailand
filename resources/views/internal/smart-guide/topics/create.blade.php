@extends('internal.layouts.app')

@section('title', 'Add Smart Guide Topic')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Add Smart Guide Topic</h2>
        <p class="text-sm text-slate-400 mt-1">You can add required documents, steps, and locations after saving.</p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-300 text-sm px-4 py-3 mb-4">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('internal.smart-guide-topics.store') }}" class="portal-card p-5 max-w-2xl">
        @include('internal.smart-guide.topics.form', ['topic' => null])
    </form>
@endsection
