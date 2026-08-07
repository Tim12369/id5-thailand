@extends('internal.layouts.app')

@section('title', 'Create Alert')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Create Alert</h2>
        <p class="text-sm text-slate-400 mt-1">This will appear prominently on the public homepage once published and within its active dates.</p>
    </div>

    <form method="POST" action="{{ route('internal.cms.alerts.store') }}">
        @include('internal.cms.alerts.form', ['article' => null])
    </form>
@endsection
