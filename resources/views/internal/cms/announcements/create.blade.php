@extends('internal.layouts.app')

@section('title', 'Create Announcement')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Create Announcement</h2>
        <p class="text-sm text-slate-400 mt-1">This will appear on the public homepage once published and within its active dates.</p>
    </div>

    <form method="POST" action="{{ route('internal.cms.announcements.store') }}">
        @include('internal.cms.announcements.form', ['article' => null])
    </form>
@endsection
