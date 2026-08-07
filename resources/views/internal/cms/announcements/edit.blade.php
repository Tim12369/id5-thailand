@extends('internal.layouts.app')

@section('title', 'Edit Announcement')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-bold text-white">Edit Announcement</h2>
        <p class="text-sm text-slate-400 mt-1">{{ $article->title_en ?: $article->title_th }}</p>
    </div>

    <form method="POST" action="{{ route('internal.cms.announcements.update', $article) }}">
        @method('PUT')
        @include('internal.cms.announcements.form')
    </form>
@endsection
